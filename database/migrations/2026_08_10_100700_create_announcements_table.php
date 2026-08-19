<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named "announcements" rather than "notifications" to stay clear of
        // Laravel's own notifications table.
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->json('tag')->nullable();
            $table->json('title');
            $table->json('body')->nullable();
            $table->string('image')->nullable();

            // Matches the reader-facing preference keys so a reader who muted a
            // category never sees it in their feed.
            $table->string('category')->default('general'); // general|program|exam|results|honor|certificate

            // Null audience means everyone; otherwise scoped to one reader.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'published_at']);
        });

        Schema::create('announcement_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('announcement_user');
        Schema::dropIfExists('announcements');
    }
};
