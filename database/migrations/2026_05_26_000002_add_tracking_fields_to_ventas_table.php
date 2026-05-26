<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('conductor_id')->nullable()->after('estado');
            $table->decimal('costo_envio', 8, 2)->default(0)->after('total');
            $table->string('estado_envio')->default('pendiente')->after('conductor_id'); // pendiente, en_ruta, entregado
            $table->string('tracking_id')->unique()->nullable()->after('id');
            
            $table->foreign('conductor_id')->references('id')->on('conductores')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['conductor_id']);
            $table->dropColumn(['conductor_id', 'costo_envio', 'estado_envio', 'tracking_id']);
        });
    }
};
