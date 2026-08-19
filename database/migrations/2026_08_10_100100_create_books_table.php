<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->json('title');
            $table->json('author')->nullable();
            $table->json('description')->nullable();
            $table->string('cover')->nullable();
            $table->unsignedInteger('pages')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['level_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
