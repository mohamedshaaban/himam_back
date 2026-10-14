<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a question, an attempt and a progress row belong to a book directly.
 *
 * A book without sections carries one document and one quiz for the whole
 * book, so the three tables that were keyed on a section now accept either —
 * exactly one of the two, enforced in the application rather than by a check
 * constraint, because the portable subset of SQL has no good way to say it.
 *
 * `book_section_id` becomes nullable rather than being replaced: every existing
 * row keeps its meaning, and a book with sections behaves exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('book_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('book_section_id')->nullable()->change();
            $table->index(['book_id', 'position']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('book_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('book_section_id')->nullable()->change();
            $table->index(['user_id', 'book_id']);
        });

        Schema::table('reading_progress', function (Blueprint $table) {
            $table->foreignId('book_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('book_section_id')->nullable()->change();
            $table->unique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        // Rows that belong to a book have no section to fall back to, so they
        // go with the column. Saying so is better than leaving orphans behind
        // a nullable foreign key.
        \Illuminate\Support\Facades\DB::table('questions')->whereNotNull('book_id')->delete();
        \Illuminate\Support\Facades\DB::table('quiz_attempts')->whereNotNull('book_id')->delete();
        \Illuminate\Support\Facades\DB::table('reading_progress')->whereNotNull('book_id')->delete();

        Schema::table('reading_progress', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'book_id']);
            $table->dropConstrainedForeignId('book_id');
            $table->unsignedBigInteger('book_section_id')->nullable(false)->change();
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'book_id']);
            $table->dropConstrainedForeignId('book_id');
            $table->unsignedBigInteger('book_section_id')->nullable(false)->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['book_id', 'position']);
            $table->dropConstrainedForeignId('book_id');
            $table->unsignedBigInteger('book_section_id')->nullable(false)->change();
        });
    }
};
