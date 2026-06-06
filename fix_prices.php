<?php
use App\Models\Producto;

// Fix products where the price was truncated from e.g. 1,230.00 to 1.23
$productos = Producto::where('precio', '<', 3.00)
    ->where('nombre', 'not like', '%ETOUCH%') // Exclude actual low price items
    ->get();

$count = 0;
foreach ($productos as $producto) {
    // 1.23 -> 1230
    // 2.3 -> 2300
    // If the value is strictly less than 3, multiply by 1000
    if ($producto->precio < 3) {
        $old_precio = $producto->precio;
        $producto->precio = $producto->precio * 1000;
        $producto->save();
        echo "Fixed {$producto->nombre}: {$old_precio} -> {$producto->precio}\n";
        $count++;
    }
}

echo "Fixed $count products.\n";
