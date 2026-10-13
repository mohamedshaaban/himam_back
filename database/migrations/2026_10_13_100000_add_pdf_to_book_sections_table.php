<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A PDF per section, read in place of — or alongside — the section text.
 *
 * Nullable, because the body text stays the default: sections written before
 * this, and sections nobody has a file for, must keep working untouched. A
 * section may carry both, and the app shows the file when there is one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_sections', function (Blueprint $table) {
            $table->string('pdf')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('book_sections', function (Blueprint $table) {
            $table->dropColumn('pdf');
        });
    }
};
