<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Termini napravljeni iz rasporeda; pri promeni rasporeda se njihovi budući slobodni termini prave iznova.
     */
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->foreignId('worker_schedule_id')->nullable()->after('worker_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('worker_schedule_id');
        });
    }
};
