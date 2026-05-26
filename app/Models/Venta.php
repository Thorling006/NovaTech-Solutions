<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                // Formato Ticket de Compra Real: TKT-YYYYMMDD-[RANDOM_HEX_6_CHARS]
                $model->id = 'TKT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            }
            if (empty($model->tracking_id)) {
                // Código numérico único de 10 dígitos para seguimiento
                $model->tracking_id = (string) random_int(1000000000, 9999999999);
            }
        });
    }

    protected $fillable = [
        'cliente_id',
        'total',
        'estado',
        'fecha',
        'direccion',
        'latitud',
        'longitud',
        'horario_entrega',
        'conductor_id',
        'costo_envio',
        'estado_envio',
        'tracking_id',
        'dia_entrega_asignado',
        'hora_entrega_asignada',
        'ruta_logistica_id',
        'orden_ruta',
        'estado_entrega_geocerca',
        'intentos_entrega'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class);
    }

    public function rutaLogistica()
    {
        return $this->belongsTo(RutaLogistica::class, 'ruta_logistica_id');
    }
}
