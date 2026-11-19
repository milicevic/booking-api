<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zaštita od zauzimanja termina: koliko rezervacija sme jedan kupac (po telefonu/emailu) i koliko dugo
     * rezervacija sme da čeka potvrdu pre nego što se sama odbije. 0 = bez ograničenja.
     */
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_active_bookings_per_customer')->default(3)->after('calendar_invites');
            $table->unsignedSmallInteger('max_daily_bookings_per_customer')->default(5)->after('max_active_bookings_per_customer');
            $table->unsignedSmallInteger('pending_expiry_hours')->default(12)->after('max_daily_bookings_per_customer');
        });
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropColumn(['max_active_bookings_per_customer', 'max_daily_bookings_per_customer', 'pending_expiry_hours']);
        });
    }
};
