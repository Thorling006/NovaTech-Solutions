<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\CatalogoController::class, 'index'])->name('home');
Route::post('/checkout', [\App\Http\Controllers\CatalogoController::class, 'checkout'])->name('checkout');
Route::get('/invoice/{id}/download', [\App\Http\Controllers\CatalogoController::class, 'downloadInvoice'])->name('invoice.download');

Route::get('/dashboard', function () {
    if (auth()->user()->role_id == 4) {
        return redirect()->route('conductor.dashboard');
    }

    $totalProductos = \App\Models\Producto::count();
    $totalCategorias = \App\Models\Categoria::count();
    $productosStockBajo = \App\Models\Producto::where('estado', 'stock_bajo')->get();
    $productosAgotados = \App\Models\Producto::where('estado', 'agotado')->get();
    
    // Validar si la relación venta.cliente existe en el modelo, si no, removerla. Asumiremos que solo producto y user se necesitan aquí.
    $ultimosMovimientos = \App\Models\MovimientoInventario::with(['producto', 'user'])
        ->orderBy('fecha', 'desc')
        ->take(5)
        ->get();
        
    $totalVentas = \App\Models\Venta::count();
    $ingresosTotales = \App\Models\Venta::where('estado', 'completada')->sum('total');

    // Métricas inteligentes para gráficos
    $productosMasVendidos = \App\Models\Producto::select('productos.id', 'productos.nombre', \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(detalle_ventas.cantidad), 0) as total_vendido'))
        ->leftJoin('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
        ->groupBy('productos.id', 'productos.nombre')
        ->orderBy('total_vendido', 'desc')
        ->take(5)
        ->get();

    $productosMenosVendidos = \App\Models\Producto::select('productos.id', 'productos.nombre', \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(detalle_ventas.cantidad), 0) as total_vendido'))
        ->leftJoin('detalle_ventas', 'productos.id', '=', 'detalle_ventas.producto_id')
        ->groupBy('productos.id', 'productos.nombre')
        ->orderBy('total_vendido', 'asc')
        ->take(5)
        ->get();

    $ventasAnuales = \App\Models\Venta::whereYear('fecha', date('Y'))
        ->where('estado', 'completada')
        ->get();
        
    $ventasPorMes = collect(range(1, 12))->map(function ($mes) use ($ventasAnuales) {
        $total = $ventasAnuales->filter(function ($venta) use ($mes) {
            return \Carbon\Carbon::parse($venta->fecha)->month == $mes;
        })->sum('total');
        
        return [
            'mes' => $mes,
            'total_ventas' => $total
        ];
    });

    return Inertia::render('Dashboard', [
        'metricas' => [
            'totalProductos' => $totalProductos,
            'totalCategorias' => $totalCategorias,
            'productosStockBajo' => $productosStockBajo,
            'productosAgotados' => $productosAgotados,
            'ultimosMovimientos' => $ultimosMovimientos,
            'totalVentas' => $totalVentas,
            'ingresosTotales' => $ingresosTotales,
            'productosMasVendidos' => $productosMasVendidos,
            'productosMenosVendidos' => $productosMenosVendidos,
            'ventasPorMes' => $ventasPorMes
        ]
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas para Superusuario
    Route::middleware('role:Superusuario')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show']);
        Route::resource('ventas', \App\Http\Controllers\VentaController::class)->only(['edit', 'update', 'destroy']);
        Route::resource('movimientos', \App\Http\Controllers\MovimientoInventarioController::class)->only(['edit', 'update', 'destroy']);
    });

    // Rutas para Superusuario y Administrador
    Route::middleware('role:Superusuario,Administrador')->group(function () {
        Route::resource('categorias', \App\Http\Controllers\CategoriaController::class)->except(['show']);
        Route::resource('productos', \App\Http\Controllers\ProductoController::class)->except(['show']);
        Route::resource('ventas', \App\Http\Controllers\VentaController::class)->only(['index', 'show']);
        
        // Rutas Inteligentes
        Route::get('rutas', [\App\Http\Controllers\RutaController::class, 'index'])->name('rutas.index');
        Route::post('/rutas', [\App\Http\Controllers\RutaController::class, 'storeRuta'])->name('rutas.store');
        Route::delete('rutas/{ruta}', [\App\Http\Controllers\RutaController::class, 'destroyRuta'])->name('rutas.destroy');
    });

    // Rutas para todos los roles del sistema (Superusuario, Administrador, Empleado)
    Route::middleware('role:Superusuario,Administrador,Empleado')->group(function () {
        Route::resource('movimientos', \App\Http\Controllers\MovimientoInventarioController::class)->only(['index', 'create', 'store']);
    });

    // Rutas para Conductor
    Route::middleware('role:Conductor')->group(function () {
        Route::get('conductor/dashboard', [\App\Http\Controllers\ConductorPanelController::class, 'index'])->name('conductor.dashboard');
        Route::post('conductor/vehicle', [\App\Http\Controllers\ConductorPanelController::class, 'updateVehicle'])->name('conductor.vehicle.update');
        Route::post('/conductor/ruta/{ruta}/iniciar', [\App\Http\Controllers\ConductorPanelController::class, 'iniciarRuta'])->name('conductor.ruta.iniciar');
        Route::post('/conductor/venta/{venta}/llegar', [\App\Http\Controllers\ConductorPanelController::class, 'llegarAlPunto'])->name('conductor.venta.llegar');
        Route::post('/conductor/venta/{venta}/finalizar', [\App\Http\Controllers\ConductorPanelController::class, 'finalizarPunto'])->name('conductor.venta.finalizar');
        Route::post('/conductor/ruta/{ruta}/llegar-almacen', [\App\Http\Controllers\ConductorPanelController::class, 'llegarAlAlmacen'])->name('conductor.ruta.llegar_almacen');
        Route::post('/conductor/ruta/{ruta}/finalizar', [\App\Http\Controllers\ConductorPanelController::class, 'finalizarRuta'])->name('conductor.ruta.finalizar');
        Route::post('/conductor/ruta/{ruta}/cancelar', [\App\Http\Controllers\ConductorPanelController::class, 'cancelarRuta'])->name('conductor.ruta.cancelar');
    });
});

// Rutas públicas de seguimiento
Route::get('/tracking', [\App\Http\Controllers\TrackingController::class, 'index'])->name('tracking.index');
Route::post('/tracking/search', [\App\Http\Controllers\TrackingController::class, 'search'])->name('tracking.search');

require __DIR__.'/auth.php';
