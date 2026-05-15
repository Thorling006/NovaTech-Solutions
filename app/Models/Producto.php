<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'precio',
        'stock_actual',
        'stock_minimo',
        'estado',
        'imagen'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function detalleVentas()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
