<?php

use App\Console\Commands\PublishSamplePdf;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Gives sections that predate the PDF field the placeholder document.
 *
 * The seeder attaches it to new content, but it only runs on an empty database
 * — and the deployed one is no longer empty, so every existing section was left
 * with a null. This fills them in once.
 *
 * Only where `pdf` is null: a section an administrator has already given real
 * material to is not touched, and clearing the field stays an administrator's
 * decision rather than something a deploy undoes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The rows are about to point at this file, so put it there first.
        Artisan::call(PublishSamplePdf::class);

        DB::table('book_sections')
            ->whereNull('pdf')
            ->update(['pdf' => '/storage/'.PublishSamplePdf::PATH]);
    }

    public function down(): void
    {
        DB::table('book_sections')
            ->where('pdf', '/storage/'.PublishSamplePdf::PATH)
            ->update(['pdf' => null]);
    }
};
