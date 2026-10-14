<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A document for the whole book, for books that have no sections.
 *
 * Translatable from the start, like the section equivalent: a programme taught
 * in four languages needs four documents, and adding the column as JSON now
 * avoids converting it later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->json('pdf')->nullable()->after('cover');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('pdf');
        });
    }
};
