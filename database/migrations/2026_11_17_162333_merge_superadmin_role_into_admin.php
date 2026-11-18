<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Uloga superadmin je spojena sa admin: postojeći superadmin nalozi postaju admin.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin', 'tenant_id' => null]);

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'client', 'worker'))");
    }

    /**
     * Vraća samo ograničenje; koji su nalozi bili superadmin ne može da se zna.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('superadmin', 'admin', 'client', 'worker'))");
    }
};
