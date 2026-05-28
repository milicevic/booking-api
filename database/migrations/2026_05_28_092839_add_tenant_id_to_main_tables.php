<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['users', 'slots', 'bookings', 'worker_profiles', 'client_profiles'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete()->after('id');
            });
        }
    }

    public function down(): void
    {
        $tables = ['users', 'slots', 'bookings', 'worker_profiles', 'client_profiles'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeignIdFor(\App\Models\Tenant::class);
            });
        }
    }
};
