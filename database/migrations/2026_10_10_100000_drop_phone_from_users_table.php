<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the reader's phone number.
 *
 * Registration stopped asking for it, and neither the account screen nor the
 * dashboard had a use for a number nobody was collecting. Holding a personal
 * contact detail that nothing reads is a liability rather than an asset.
 *
 * The association's own support number is unaffected — that lives on
 * contact_details and is published deliberately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
        });
    }
};
