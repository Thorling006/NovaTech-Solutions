<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas_logisticas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('conductor_id')->nullable()->constrained('conductores')->onDelete('set null');
            $table->string('estado')->default('pendiente'); // pendiente, en_curso, finalizada
            $table->date('fecha_programada')->nullable();
            $table->time('hora_programada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutas_logisticas');
    }
};
