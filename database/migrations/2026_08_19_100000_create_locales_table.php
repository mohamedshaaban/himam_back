<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Languages move from config to the database so an administrator can add
        // or retire one without a deploy. Translatable content is already stored
        // as a locale => text map, so a new language needs no schema change and
        // no new columns anywhere.
        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');          // endonym, e.g. العربية
            $table->string('english_name');
            $table->string('direction', 3)->default('ltr'); // ltr | rtl
            $table->boolean('is_active')->default(true);

            // Exactly one row carries this; it is what an unknown or disabled
            // locale falls back to.
            $table->boolean('is_default')->default(false);

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
