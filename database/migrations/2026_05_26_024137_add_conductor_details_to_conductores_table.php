<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conductores', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('vehiculo_tipo', ['moto', 'carro', 'camion'])->default('moto');
            $table->decimal('latitud_actual', 10, 8)->nullable();
            $table->decimal('longitud_actual', 11, 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conductores', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'vehiculo_tipo', 'latitud_actual', 'longitud_actual']);
        });
    }
};
