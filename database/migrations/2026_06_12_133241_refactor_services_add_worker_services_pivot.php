<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop worker_id from services — service belongs to tenant, not worker
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['worker_id']);
            $table->dropColumn('worker_id');
        });

        // Pivot: many-to-many between workers and services
        Schema::create('worker_services', function (Blueprint $table) {
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->primary(['worker_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_services');

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('worker_id')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }
};
