<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ko potvrđuje rezervacije (owner | worker | auto) i kada je vlasnik završio onboarding.
     * auto_confirm_bookings ostaje radi kompatibilnosti i prati confirmation_mode = auto.
     */
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->string('confirmation_mode', 10)->default('owner')->after('auto_confirm_bookings');
            $table->timestamp('onboarding_completed_at')->nullable()->after('customer_contact');
        });

        DB::table('client_profiles')->where('auto_confirm_bookings', true)->update(['confirmation_mode' => 'auto']);

        // Postojeći vlasnici koji već imaju radnike ne treba ponovo da prolaze onboarding
        DB::table('client_profiles')
            ->whereExists(fn ($query) => $query->select(DB::raw(1))
                ->from('users')
                ->whereColumn('users.client_id', 'client_profiles.user_id'))
            ->update(['onboarding_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropColumn(['confirmation_mode', 'onboarding_completed_at']);
        });
    }
};
