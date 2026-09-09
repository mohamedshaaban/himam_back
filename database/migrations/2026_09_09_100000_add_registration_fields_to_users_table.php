<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The fields the association's own registration form collects.
 *
 * All nullable: the accounts that already exist were created before the form
 * asked for any of this, and a certificate should not be blocked on a reader
 * going back to fill in their age band.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 16)->nullable()->after('name');
            $table->string('age_band', 16)->nullable()->after('gender');
            $table->string('education_level', 32)->nullable()->after('age_band');
            $table->string('country', 120)->nullable()->after('city');

            // Kept apart from `phone`: the form asks for a WhatsApp number with
            // its international code, which is often not the number a reader
            // would give for calls.
            $table->string('whatsapp', 32)->nullable()->after('phone');

            // Consent to be emailed about the programme, which is a separate
            // question from the in-app notification preferences.
            $table->boolean('accepts_email')->default(false)->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender', 'age_band', 'education_level', 'country', 'whatsapp', 'accepts_email',
            ]);
        });
    }
};
