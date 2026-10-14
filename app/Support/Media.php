<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Turns a stored media reference into a URL any client can fetch.
 *
 * The API serves a web app and a mobile app, and only the web app could ever
 * resolve a root-relative path — a native client has no origin to resolve it
 * against. So every media field leaves here absolute.
 *
 * Three kinds of value are stored, and each resolves differently:
 *
 *   https://…/x.jpg   already absolute — a remote disk, or an external image
 *   uploads/x.pdf     uploaded through the dashboard, on the media disk
 *   assets/badge.png  shipped in the image, served by the web server
 */
class Media
{
    public static function url(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, 'data:')) {
            return $value;
        }

        $path = ltrim($value, '/');

        // A stored '/storage/uploads/x' is the public disk's own URL form; the
        // disk knows where that actually lives, which matters once uploads move
        // to object storage and stop being served from this host at all.
        if (str_starts_with($path, 'storage/')) {
            return self::fromDisk(substr($path, strlen('storage/')));
        }

        if (str_starts_with($path, 'uploads/')) {
            return self::fromDisk($path);
        }

        // Anything else ships with the image — covers, badges, the certificate
        // template — and is served straight off this host.
        return rtrim(config('app.url') ?: url('/'), '/').'/'.$path;
    }

    /**
     * Resolves a path on the media disk.
     *
     * A remote disk returns a genuine absolute URL of its own. A local one
     * returns something built from APP_URL, which is the same thing we would
     * build here, so either way the caller gets an address that resolves.
     */
    private static function fromDisk(string $path): string
    {
        $disk = config('himam.media_disk', 'public');

        $url = Storage::disk($disk)->url($path);

        if (preg_match('#^(https?:)?//#i', $url)) {
            return $url;
        }

        // A local disk with no APP_URL set hands back a relative path; make it
        // absolute rather than passing the problem on to the client.
        return rtrim(config('app.url') ?: url('/'), '/').'/'.ltrim($url, '/');
    }
}
