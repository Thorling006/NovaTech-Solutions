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
            $table->timestamp('llegada_almacen_inicial')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutas_logisticas', function (Blueprint $table) {
            $table->dropColumn('llegada_almacen_inicial');
        });
    }
};
