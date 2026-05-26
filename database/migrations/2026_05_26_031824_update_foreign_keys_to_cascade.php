<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('cascade');
        });

        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            
            $table->dropForeign(['venta_id']);
            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
        });

        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            
            $table->dropForeign(['venta_id']);
            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->foreign('categoria_id')->references('id')->on('categorias');
        });

        Schema::table('movimiento_inventarios', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->foreign('producto_id')->references('id')->on('productos');
            
            $table->dropForeign(['venta_id']);
            $table->foreign('venta_id')->references('id')->on('ventas');
        });

        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->foreign('producto_id')->references('id')->on('productos');
            
            $table->dropForeign(['venta_id']);
            $table->foreign('venta_id')->references('id')->on('ventas');
        });
    }
};
