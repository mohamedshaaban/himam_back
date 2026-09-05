<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // About and Privacy are the same shape — a title and a body of rich text
        // per language — so they share one table keyed by slug rather than
        // getting a table each. A future "terms" page needs no migration.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->json('question');
            $table->json('answer');
            $table->string('category')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        // A single row of support details. Kept as a table rather than config so
        // the association can change a phone number without a deploy.
        Schema::create('contact_details', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('website')->nullable();
            $table->json('address')->nullable();
            $table->json('working_hours')->nullable();
            $table->json('note')->nullable();
            $table->json('social')->nullable(); // [{platform, url}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_details');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('pages');
    }
};
