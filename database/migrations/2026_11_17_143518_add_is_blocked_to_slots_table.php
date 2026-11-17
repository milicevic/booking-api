<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blokiran termin ostaje u kalendaru, ali se ne nudi kupcima (is_available je izračunat iz rezervacija).
     */
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('is_available');
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });
    }
};
