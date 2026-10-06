<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reading programmes: an ordered container of books with its own audience.
 *
 * Deliberately absent is a per-user per-book status column. Whether a reader has
 * finished a book is already decided by which of its sections they have passed —
 * that is what issues certificates and awards badges today. Storing a second
 * answer here would create two sources of truth that drift the first time a
 * section is added to a book someone already "completed".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();

            // general | sequential | selective
            $table->string('type', 16)->default('general');

            // Whether the programme is listed for readers at all. General
            // programmes are always public and selective ones never are; the
            // column exists for sequential programmes, whose audience the
            // specification leaves to the administrator.
            $table->boolean('is_public')->default(true);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->string('cover')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('book_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();

            // Meaningless for a general programme, mandatory for a sequential
            // one — it is what "the previous book" refers to.
            $table->unsignedInteger('order_index')->default(0);

            $table->timestamps();

            $table->unique(['program_id', 'book_id']);
            $table->index(['program_id', 'order_index']);
        });

        Schema::create('program_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_user');
        Schema::dropIfExists('book_program');
        Schema::dropIfExists('programs');
    }
};
