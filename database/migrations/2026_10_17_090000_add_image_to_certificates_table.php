<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Artwork for a certificate.
 *
 * Nullable, and normally left so: a certificate with nothing of its own falls
 * back to the programme's template, which is what every certificate uses until
 * someone designs a different one for a particular level or book. Storing the
 * path rather than the image keeps the row small and lets the file move to
 * object storage without a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('image')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
