<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('city')->nullable()->after('phone');
            $table->string('avatar')->nullable()->after('city');
            $table->string('role')->default('student')->after('avatar');
            $table->string('locale', 5)->default('ar')->after('role');
            $table->unsignedBigInteger('points')->default(0)->after('locale');
            $table->foreignId('level_id')->nullable()->after('points')->constrained()->nullOnDelete();

            $table->index('role');
            $table->index('points');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['points']);
            $table->dropColumn(['phone', 'city', 'avatar', 'role', 'locale', 'points', 'level_id']);
        });
    }
};
