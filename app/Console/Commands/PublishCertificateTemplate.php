<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Puts the certificate artwork back on the media disk.
 *
 * The same problem the sample PDF has: certificates point at the file by path,
 * and on a host with an ephemeral filesystem the row outlives the upload. A
 * deploy would otherwise leave every certificate pointing at nothing.
 *
 * Both forms are published. The PNG is what clients should use — few mobile
 * image loaders render SVG without a plugin — and the SVG is there for print
 * and for anyone regenerating the artwork.
 */
class PublishCertificateTemplate extends Command
{
    protected $signature = 'himam:publish-certificate-template {--force : Overwrite the files if they are already there}';

    protected $description = 'Copy the certificate artwork onto the media disk';

    /** The path a certificate carries when nothing else was uploaded for it. */
    public const PATH = 'uploads/certificate-template.png';

    public const VECTOR_PATH = 'uploads/certificate-template.svg';

    private const SOURCES = [
        self::PATH => __DIR__.'/../../../database/seeders/assets/certificate-template.png',
        self::VECTOR_PATH => __DIR__.'/../../../database/seeders/assets/certificate-template.svg',
    ];

    public function handle(): int
    {
        $disk = Storage::disk(config('himam.media_disk', 'public'));
        $published = 0;

        foreach (self::SOURCES as $path => $source) {
            if (! is_file($source)) {
                $this->warn("Missing {$source}; skipped.");

                continue;
            }

            if ($disk->exists($path) && ! $this->option('force')) {
                $this->line("{$path} already present.");

                continue;
            }

            $disk->put($path, file_get_contents($source));
            $this->info("Published {$path}");
            $published++;
        }

        return self::SUCCESS;
    }
}
