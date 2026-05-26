<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('ruta_logistica_id')->nullable()->constrained('rutas_logisticas')->onDelete('set null');
            $table->integer('orden_ruta')->nullable();
            $table->string('estado_entrega_geocerca')->default('pendiente'); // pendiente, en_camino, en_el_punto, entregado, fallido
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['ruta_logistica_id']);
            $table->dropColumn(['ruta_logistica_id', 'orden_ruta', 'estado_entrega_geocerca']);
        });
    }
};
