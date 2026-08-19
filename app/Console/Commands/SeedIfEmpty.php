<?php

namespace App\Console\Commands;

use App\Models\Level;
use Illuminate\Console\Command;

/**
 * Seeds the programme's content, but only when there is none.
 *
 * Managed hosts redeploy on every push and free tiers rarely offer shell
 * access, so seeding has to happen during boot. Running db:seed unconditionally
 * would stack up a duplicate catalogue on every deploy; checking first makes it
 * safe to leave in the startup path permanently.
 */
class SeedIfEmpty extends Command
{
    protected $signature = 'himam:seed-if-empty';

    protected $description = 'Seed the catalogue only if the database has no content yet';

    public function handle(): int
    {
        if (Level::query()->exists()) {
            $this->info('Catalogue already present — skipping seed.');

            return self::SUCCESS;
        }

        $this->info('Empty database detected — seeding the catalogue.');
        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
