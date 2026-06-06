<?php

use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\User;

// Cargar bootstrap de Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    DB::beginTransaction();

    $adminUser = User::where('role_id', 1)->first() ?? User::first();
    $adminUserId = $adminUser ? $adminUser->id : 1;

    $productos = Producto::all();
    echo "Actualizando " . $productos->count() . " productos...\n";

    foreach ($productos as $producto) {
        // Poner stock mínimo en 5
        $producto->stock_minimo = 5;
        // Poner stock actual en 10 (las 10 unidades de entrada)
        $producto->stock_actual = 10;
        $producto->estado = 'disponible';
        $producto->stock_alert_sent = false;
        $producto->save();

        // Eliminar movimientos de inventario anteriores para este producto
        MovimientoInventario::where('producto_id', $producto->id)->delete();

        // Registrar movimiento de entrada de 10 unidades
        MovimientoInventario::create([
            'producto_id' => $producto->id,
            'tipo' => 'entrada',
            'cantidad' => 10,
            'stock_anterior' => 0,
            'stock_resultante' => 10,
            'user_id' => $adminUserId,
            'fecha' => now()
        ]);
        
        echo " - Producto ID {$producto->id} ('{$producto->nombre}'): Stock mínimo=5, Stock actual=10. Registrada entrada de 10 unidades.\n";
    }

    DB::commit();
    echo "¡Proceso de inicialización de inventario completado con éxito!\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
