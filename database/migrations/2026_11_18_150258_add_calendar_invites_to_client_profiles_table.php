<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Da li kupac uz potvrdu dobija poziv u kalendar (Google link i .ics sa podsetnikom); vlasnik može da isključi.
     */
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->boolean('calendar_invites')->default(true)->after('reschedule_cutoff_hours');
        });
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropColumn('calendar_invites');
        });
    }
};
