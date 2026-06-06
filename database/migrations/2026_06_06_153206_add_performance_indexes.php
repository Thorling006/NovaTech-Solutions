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
        Schema::table('productos', function (Blueprint $table) {
            $table->index('estado');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->index('tracking_id');
            $table->index('estado_envio');
        });

        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $table->index('tipo');
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $table->dropIndex(['tipo']);
            $table->dropIndex(['fecha']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['tracking_id']);
            $table->dropIndex(['estado_envio']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['estado']);
        });
    }
};
