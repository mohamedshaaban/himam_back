<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Puts the placeholder reading material back on the media disk.
 *
 * Sections point at it by path, and on a host with an ephemeral filesystem that
 * path outlives the file: the database row survives a deploy and the upload does
 * not, leaving a section pointing at nothing. Running this on every boot costs a
 * 3 KB copy and keeps the two in step until real material replaces it.
 *
 * It only ever writes the file. Which sections use it is a separate question,
 * decided once by a migration, so an administrator who clears the field does not
 * find it back after the next deploy.
 */
class PublishSamplePdf extends Command
{
    protected $signature = 'himam:publish-sample-pdf {--force : Overwrite the file if it is already there}';

    protected $description = 'Copy the placeholder section PDF onto the media disk';

    public const PATH = 'uploads/sample-section.pdf';

    public const SOURCE = __DIR__.'/../../../database/seeders/assets/sample-section.pdf';

    public function handle(): int
    {
        if (! is_file(self::SOURCE)) {
            $this->warn('No sample PDF in the repository; nothing to publish.');

            return self::SUCCESS;
        }

        $disk = Storage::disk(config('himam.media_disk', 'public'));

        if ($disk->exists(self::PATH) && ! $this->option('force')) {
            $this->line('Sample PDF already present.');

            return self::SUCCESS;
        }

        $disk->put(self::PATH, file_get_contents(self::SOURCE));

        $this->info('Sample PDF published to '.self::PATH);

        return self::SUCCESS;
    }
}
