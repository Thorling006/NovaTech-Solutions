<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->date('dia_entrega_asignado')->nullable();
            $table->time('hora_entrega_asignada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['dia_entrega_asignado', 'hora_entrega_asignada']);
        });
    }
};
