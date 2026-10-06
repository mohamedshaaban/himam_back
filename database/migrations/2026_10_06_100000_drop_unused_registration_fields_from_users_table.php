<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the registration fields the association decided not to ask for.
 *
 * The shorter the form, the more people finish it, and none of these three were
 * being used to decide anything — the programme tracks progress, not age or
 * schooling. `phone` stays: it is still the only way to reach a reader off the
 * platform.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'age_band', 'education_level']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('age_band', 16)->nullable()->after('gender');
            $table->string('education_level', 32)->nullable()->after('age_band');
            $table->string('whatsapp', 32)->nullable()->after('phone');
        });
    }
};
