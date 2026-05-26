<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conductor extends Model
{
    use HasFactory;

    protected $table = 'conductores';

    protected $fillable = [
        'nombre',
        'foto_url',
        'estado',
        'user_id',
        'vehiculo_tipo',
        'latitud_actual',
        'longitud_actual'
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'conductor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
