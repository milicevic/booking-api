<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stalni raspored radnika; iz njega se termini prave unapred (slots:extend-schedules).
     */
    public function up(): void
    {
        Schema::create('worker_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('weekdays');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_duration');
            $table->json('breaks');
            // Do kog dana su termini već napravljeni; sledeće pokretanje nastavlja odatle
            $table->date('generated_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_schedules');
    }
};
