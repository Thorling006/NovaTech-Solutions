<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [\App\Http\Controllers\CatalogoController::class, 'index'])->name('home');
Route::post('/checkout', [\App\Http\Controllers\CatalogoController::class, 'checkout'])->name('checkout');

Route::get('/dashboard', function () {
    $totalProductos = \App\Models\Producto::count();
    $totalCategorias = \App\Models\Categoria::count();
    $productosStockBajo = \App\Models\Producto::where('estado', 'stock_bajo')->get();
    $productosAgotados = \App\Models\Producto::where('estado', 'agotado')->get();
    $ultimosMovimientos = \App\Models\MovimientoInventario::with(['producto', 'user'])
        ->orderBy('fecha', 'desc')
        ->take(5)
        ->get();
    $totalVentas = \App\Models\Venta::count();

    return Inertia::render('Dashboard', [
        'metricas' => [
            'totalProductos' => $totalProductos,
            'totalCategorias' => $totalCategorias,
            'productosStockBajo' => $productosStockBajo,
            'productosAgotados' => $productosAgotados,
            'ultimosMovimientos' => $ultimosMovimientos,
            'totalVentas' => $totalVentas
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
    });

    // Rutas para Superusuario y Administrador
    Route::middleware('role:Superusuario,Administrador')->group(function () {
        Route::resource('categorias', \App\Http\Controllers\CategoriaController::class)->except(['show']);
        Route::resource('productos', \App\Http\Controllers\ProductoController::class)->except(['show']);
        Route::resource('ventas', \App\Http\Controllers\VentaController::class)->only(['index', 'show']);
    });

    // Rutas para todos los roles del sistema (Superusuario, Administrador, Empleado)
    Route::middleware('role:Superusuario,Administrador,Empleado')->group(function () {
        Route::resource('movimientos', \App\Http\Controllers\MovimientoInventarioController::class)->only(['index', 'create', 'store']);
    });
});

require __DIR__.'/auth.php';
