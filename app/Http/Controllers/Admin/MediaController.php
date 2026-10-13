<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * File uploads: images for covers, badges, slides and rich-text content, and
 * PDFs for section reading material.
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

    /** Where uploads land — the container filesystem, or object storage. */
    private function disk(): string
    {
        return config('himam.media_disk', 'public');
    }

    /**
     * A URL the browser can actually resolve.
     *
     * For a local disk Laravel builds this from APP_URL, which is wrong more
     * often than it is right: wrong port in development, wrong scheme behind a
     * proxy, stale after a domain change. A root-relative path sidesteps all of
     * that — the client resolves it against whichever origin served the API.
     * Remote disks (S3, R2) return a genuine absolute URL, which is kept.
     */
    private function publicUrl(string $path): string
    {
        if (config('filesystems.disks.'.$this->disk().'.driver') === 'local') {
            return '/storage/'.ltrim($path, '/');
        }

        return Storage::disk($this->disk())->url($path);
    }

    /**
     * Previously uploaded images, newest first — this backs the picker's
     * "browse" tab so an author can reuse an image instead of re-uploading it.
     */
    public function index(): JsonResponse
    {
        $disk = Storage::disk($this->disk());

        if (! $disk->exists(self::DIRECTORY)) {
            return response()->json(['data' => []]);
        }

        $files = collect($disk->files(self::DIRECTORY))
            ->map(fn (string $path) => [
                'path' => $path,
                'url' => $this->publicUrl($path),
                'name' => basename($path),
                // The picker filters on this: an image field should not offer a
                // PDF, and a PDF field should not offer a cover image.
                'kind' => Str::lower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf' ? 'pdf' : 'image',
                'size' => $disk->size($path),
                'modified_at' => $disk->lastModified($path),
            ])
            ->sortByDesc('modified_at')
            ->values();

        return response()->json(['data' => $files]);
    }

    public function store(Request $request): JsonResponse
    {
        // Images and PDFs take different rules. `image` verifies the file really
        // is an image, which stops a renamed script being uploaded and later
        // served — but it rejects a PDF, so a PDF is checked by its own mime
        // type instead. `mimetypes` reads the file's actual content rather than
        // trusting the extension, so the same protection holds.
        $isPdf = $request->file('file')?->getClientOriginalExtension() === 'pdf';

        $request->validate([
            'file' => $isPdf
                // A scanned section runs to a few megabytes; images never do.
                ? ['required', 'file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:20480']
                : ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:4096'],
        ]);

        $file = $request->file('file');

        // Never reuse the client's filename: it can collide, carry a path, or
        // hold a second extension. Keep a readable slug for humans and add
        // entropy for uniqueness.
        $name = sprintf(
            '%s-%s.%s',
            Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file',
            Str::lower(Str::random(8)),
            $file->getClientOriginalExtension(),
        );

        $path = $file->storeAs(self::DIRECTORY, $name, 'public');
        $disk = Storage::disk($this->disk());

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => $this->publicUrl($path),
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

        Storage::disk($this->disk())->delete($path);

        return response()->json(['message' => __('File deleted.')]);
    }
}
