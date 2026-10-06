<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separates a reader enrolling themselves from an administrator assigning them.
 *
 * Both are rows in the same table because both answer "is this reader in this
 * programme", but they are not interchangeable: an assignment is what makes a
 * selective programme visible in the first place, so letting a self-enrolment
 * count as one would let anybody walk into a programme they were never meant
 * to see.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_user', function (Blueprint $table) {
            // Existing rows were all made by an administrator.
            $table->string('source', 16)->default('assigned')->after('user_id');
            $table->timestamp('enrolled_at')->nullable()->after('assigned_at');
        });
    }

    public function down(): void
    {
        Schema::table('program_user', function (Blueprint $table) {
            $table->dropColumn(['source', 'enrolled_at']);
        });
    }
};
