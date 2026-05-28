<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('theme')->default('minimal')->after('app_name');
            $table->enum('deploy_status', ['pending', 'pending_deploy', 'deployed'])->default('pending')->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['theme', 'deploy_status']);
        });
    }
};
