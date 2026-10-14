<?php

use App\Console\Commands\PublishSamplePdf;
use App\Services\LocaleRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives the placeholder document an entry in every language.
 *
 * It already reached every reader through the per-field fallback, but only the
 * fallback locale held a value — so the dashboard showed one filled slot and
 * three empty ones, which reads as missing rather than as deliberate.
 *
 * Only the placeholder is expanded. Copying a real upload across languages
 * would be a guess: an Arabic document is not the French one, and presenting it
 * as though it were is worse than leaving the slot empty for someone to fill.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sample = '/storage/'.PublishSamplePdf::PATH;
        $locales = app(LocaleRegistry::class)->codes();

        if ($locales === []) {
            return;
        }

        DB::table('book_sections')
            ->whereNotNull('pdf')
            ->orderBy('id')
            ->chunkById(200, function ($sections) use ($sample, $locales) {
                foreach ($sections as $section) {
                    $map = json_decode((string) $section->pdf, true);

                    if (! is_array($map) || $map === []) {
                        continue;
                    }

                    // Every value is the placeholder, or this row holds real
                    // material and is none of our business.
                    if (array_values(array_unique($map)) !== [$sample]) {
                        continue;
                    }

                    $filled = array_fill_keys($locales, $sample);

                    if ($filled === $map) {
                        continue;
                    }

                    DB::table('book_sections')
                        ->where('id', $section->id)
                        ->update(['pdf' => json_encode(
                            $filled,
                            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                        )]);
                }
            });
    }

    public function down(): void
    {
        $sample = '/storage/'.PublishSamplePdf::PATH;
        $fallback = config('app.fallback_locale', 'en');

        DB::table('book_sections')
            ->whereNotNull('pdf')
            ->orderBy('id')
            ->chunkById(200, function ($sections) use ($sample, $fallback) {
                foreach ($sections as $section) {
                    $map = json_decode((string) $section->pdf, true);

                    if (! is_array($map) || array_values(array_unique($map)) !== [$sample]) {
                        continue;
                    }

                    DB::table('book_sections')
                        ->where('id', $section->id)
                        ->update(['pdf' => json_encode(
                            [$fallback => $sample],
                            JSON_UNESCAPED_SLASHES,
                        )]);
                }
            });
    }
};
