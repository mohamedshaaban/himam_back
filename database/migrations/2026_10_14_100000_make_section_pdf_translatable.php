<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes a section's document translatable, like its title and body.
 *
 * A programme that teaches in four languages needs four documents, not one.
 *
 * Done as add-copy-drop-rename rather than a type change in place: the column
 * holds a bare path today, and asking the database to reinterpret that as JSON
 * fails on MySQL and quietly keeps a string on SQLite. Copying the value into a
 * real JSON column states the conversion instead of hoping for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_sections', function (Blueprint $table) {
            $table->json('pdf_translations')->nullable()->after('pdf');
        });

        $fallback = config('app.fallback_locale', 'en');

        // The existing path was written before there were languages, so it
        // becomes the fallback entry — t() serves it for every locale that has
        // nothing of its own.
        DB::table('book_sections')
            ->whereNotNull('pdf')
            ->where('pdf', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($sections) use ($fallback) {
                foreach ($sections as $section) {
                    DB::table('book_sections')
                        ->where('id', $section->id)
                        ->update(['pdf_translations' => json_encode(
                            [$fallback => $section->pdf],
                            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                        )]);
                }
            });

        Schema::table('book_sections', function (Blueprint $table) {
            $table->dropColumn('pdf');
        });

        Schema::table('book_sections', function (Blueprint $table) {
            $table->renameColumn('pdf_translations', 'pdf');
        });
    }

    public function down(): void
    {
        Schema::table('book_sections', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('pdf');
        });

        $fallback = config('app.fallback_locale', 'en');

        // Collapsing back loses every language but one, which is the honest
        // consequence of undoing this.
        DB::table('book_sections')
            ->whereNotNull('pdf')
            ->orderBy('id')
            ->chunkById(200, function ($sections) use ($fallback) {
                foreach ($sections as $section) {
                    $map = json_decode((string) $section->pdf, true);

                    if (! is_array($map) || $map === []) {
                        continue;
                    }

                    DB::table('book_sections')
                        ->where('id', $section->id)
                        ->update(['pdf_path' => $map[$fallback] ?? reset($map)]);
                }
            });

        Schema::table('book_sections', function (Blueprint $table) {
            $table->dropColumn('pdf');
        });

        Schema::table('book_sections', function (Blueprint $table) {
            $table->renameColumn('pdf_path', 'pdf');
        });
    }
};
