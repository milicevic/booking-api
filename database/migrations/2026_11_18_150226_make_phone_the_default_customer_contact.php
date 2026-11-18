<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Podrazumevano pravilo za kontakt kupca postaje „telefon obavezan, email opcion“.
     * Firme koje su imale staro podrazumevano pravilo (email ili telefon) prelaze na novo.
     */
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->string('customer_contact', 20)->default('phone')->change();
        });

        DB::table('client_profiles')->where('customer_contact', 'email_or_phone')->update(['customer_contact' => 'phone']);
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->string('customer_contact', 20)->default('email_or_phone')->change();
        });
    }
};
