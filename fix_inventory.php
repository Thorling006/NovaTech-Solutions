<?php
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Support\Str;

$productos = Producto::all();
$count = 0;
$user = User::first(); // Asumiendo que el admin es el id 1

foreach ($productos as $producto) {
    // Check if it already has an entry movement
    $hasEntry = MovimientoInventario::where('producto_id', $producto->id)
        ->where('tipo', 'entrada')
        ->exists();

    if (!$hasEntry && $producto->stock_actual > 0) {
        MovimientoInventario::create([
            'id' => (string) Str::uuid(),
            'producto_id' => $producto->id,
            'tipo' => 'entrada',
            'cantidad' => $producto->stock_actual,
            'stock_anterior' => 0,
            'stock_resultante' => $producto->stock_actual,
            'user_id' => $user->id ?? 1,
            'fecha' => now()
        ]);
        $count++;
    }
}

echo "Se generaron $count movimientos de inventario de entrada.\n";
