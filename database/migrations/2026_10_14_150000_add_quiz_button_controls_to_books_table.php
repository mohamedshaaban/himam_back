<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Puts the quiz button under the dashboard's control.
 *
 * Whether a book offers its exam, and what the button says, are editorial
 * decisions rather than client ones: a book can be published for reading weeks
 * before its questions are ready, and the wording differs between a first
 * attempt and a resit. Both now travel with the book, so no release of the apps
 * is needed to change either.
 *
 * `quiz_button_visible` defaults to true so every existing book keeps behaving
 * exactly as it does today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->boolean('quiz_button_visible')->default(true)->after('pdf');
            $table->json('quiz_button_label')->nullable()->after('quiz_button_visible');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['quiz_button_visible', 'quiz_button_label']);
        });
    }
};
