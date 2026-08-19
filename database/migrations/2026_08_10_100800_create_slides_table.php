<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backs the carousel that appears on most screens. `screen` is the key
        // the frontend asks for (home, books, badges, ...).
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('screen');
            $table->string('image');
            $table->json('caption')->nullable();
            $table->string('href')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['screen', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
