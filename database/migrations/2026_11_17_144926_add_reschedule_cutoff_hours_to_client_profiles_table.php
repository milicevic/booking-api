<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koliko sati pre početka kupac najkasnije sme sam da pomeri rezervaciju.
     */
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('reschedule_cutoff_hours')->default(6)->after('confirmation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropColumn('reschedule_cutoff_hours');
        });
    }
};
