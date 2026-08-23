<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Image uploads for covers, badges, slides and rich-text content.
 *
 * Files land on the `public` disk, so swapping to S3 or R2 later is a matter of
 * changing FILESYSTEM_DISK — nothing here assumes a local path. That matters
 * because container filesystems are ephemeral: on a platform like Render the
 * uploads directory is wiped on every deploy, so anything beyond a demo wants
 * object storage behind this same endpoint.
 */
class MediaController extends Controller
{
    private const DIRECTORY = 'uploads';

    /**
     * Previously uploaded images, newest first — this backs the picker's
     * "browse" tab so an author can reuse an image instead of re-uploading it.
     */
    public function index(): JsonResponse
    {
        $disk = Storage::disk('public');

        if (! $disk->exists(self::DIRECTORY)) {
            return response()->json(['data' => []]);
        }

        $files = collect($disk->files(self::DIRECTORY))
            ->map(fn (string $path) => [
                'path' => $path,
                'url' => $disk->url($path),
                'name' => basename($path),
                'size' => $disk->size($path),
                'modified_at' => $disk->lastModified($path),
            ])
            ->sortByDesc('modified_at')
            ->values();

        return response()->json(['data' => $files]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            // `image` rejects anything the server can't verify as a real image,
            // which stops a renamed script being uploaded and later served.
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:4096'],
        ]);

        $file = $request->file('file');

        // Never reuse the client's filename: it can collide, carry a path, or
        // hold a second extension. Keep a readable slug for humans and add
        // entropy for uniqueness.
        $name = sprintf(
            '%s-%s.%s',
            Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image',
            Str::lower(Str::random(8)),
            $file->getClientOriginalExtension(),
        );

        $path = $file->storeAs(self::DIRECTORY, $name, 'public');
        $disk = Storage::disk('public');

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => $disk->url($path),
                'name' => $name,
                'size' => $disk->size($path),
            ],
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);

        // Confine deletes to the uploads directory so a crafted path can't reach
        // the rest of the disk.
        $path = ltrim(str_replace('..', '', $data['path']), '/');

        if (! Str::startsWith($path, self::DIRECTORY.'/')) {
            return response()->json(['message' => __('That file is outside the uploads directory.')], 422);
        }

        Storage::disk('public')->delete($path);

        return response()->json(['message' => __('File deleted.')]);
    }
}
