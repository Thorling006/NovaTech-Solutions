<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $producto = \App\Models\Producto::with('categoria')->find($model->producto_id);
                // Obtener prefijos inteligentes de la categoría y producto para coherencia y estilo
                $catPrefix = $producto && $producto->categoria ? strtoupper(substr($producto->categoria->nombre, 0, 3)) : 'GEN';
                $prodPrefix = $producto ? strtoupper(substr($producto->codigo, 0, 5)) : 'PROD';
                $tipoPrefix = $model->tipo === 'entrada' ? 'ENT' : 'SAL';
                // Formato: MOV-[CAT]-[PROD]-[TIPO]-[RANDOM_HEX_4_CHARS]
                $model->id = "MOV-{$catPrefix}-{$prodPrefix}-{$tipoPrefix}-" . strtoupper(bin2hex(random_bytes(2)));
            }
        });
    }

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'stock_anterior',
        'stock_resultante',
        'user_id',
        'fecha',
        'venta_id'
    ];

    public $timestamps = true;

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
