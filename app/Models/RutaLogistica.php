<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RutaLogistica extends Model
{
    use HasFactory;

    protected $table = 'rutas_logisticas';

    protected $fillable = [
        'nombre',
        'conductor_id',
        'estado',
        'fecha_programada',
        'hora_programada',
    ];

    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'ruta_logistica_id')->orderBy('orden_ruta', 'asc');
    }
}
