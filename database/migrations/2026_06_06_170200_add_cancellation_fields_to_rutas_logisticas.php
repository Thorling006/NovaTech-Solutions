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
        Schema::table('rutas_logisticas', function (Blueprint $table) {
            $table->text('motivo_cancelacion')->nullable();
            $table->string('foto_cancelacion', 2048)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutas_logisticas', function (Blueprint $table) {
            $table->dropColumn(['motivo_cancelacion', 'foto_cancelacion']);
        });
    }
};
