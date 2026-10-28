<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rezervacija dobija tačno vreme početka i kraja (prema trajanju usluge),
     * umesto da uvek zauzima ceo termin. slot_id ostaje termin u kom rezervacija počinje.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('service_id');
            $table->time('end_time')->nullable()->after('start_time');
        });

        // Postojeće rezervacije zadržavaju vreme svog termina
        DB::statement('UPDATE bookings SET
            start_time = (SELECT slots.start_time FROM slots WHERE slots.id = bookings.slot_id),
            end_time = (SELECT slots.end_time FROM slots WHERE slots.id = bookings.slot_id)');
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
