<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;

class CatalogoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')
            ->whereIn('estado', ['disponible', 'stock_bajo'])
            ->get();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'productos' => $productos
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'cliente.nombre' => 'required|string|max:255',
            'cliente.correo' => 'required|email|max:255',
            'cliente.telefono' => 'nullable|string|max:20',
            'carrito' => 'required|array|min:1',
            'carrito.*.producto_id' => 'required|exists:productos,id',
            'carrito.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // 1. Obtener o crear cliente
            $cliente = Cliente::firstOrCreate(
                ['correo' => $request->input('cliente.correo')],
                [
                    'nombre' => $request->input('cliente.nombre'),
                    'telefono' => $request->input('cliente.telefono'),
                ]
            );

            // 2. Crear Venta
            $venta = Venta::create([
                'cliente_id' => $cliente->id,
                'total' => 0, // Se calculará ahora
                'estado' => 'completada',
            ]);

            $total = 0;

            // Obtener el ID del SuperAdmin o un usuario genérico para registrar el movimiento
            $adminUser = User::where('role_id', 1)->first();

            // 3. Procesar carrito
            foreach ($request->carrito as $item) {
                $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);
                
                if ($producto->stock_actual < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
                }

                $subtotal = $producto->precio * $item['cantidad'];
                $total += $subtotal;

                // Crear detalle de venta
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal,
                ]);

                $stock_anterior = $producto->stock_actual;
                $stock_resultante = $stock_anterior - $item['cantidad'];

                // Crear movimiento de salida
                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'tipo' => 'salida',
                    'cantidad' => $item['cantidad'],
                    'stock_anterior' => $stock_anterior,
                    'stock_resultante' => $stock_resultante,
                    'user_id' => $adminUser->id ?? 1, // Usuario por defecto del sistema
                ]);

                // Actualizar producto
                $nuevo_estado = $producto->estado;
                if ($stock_resultante == 0) {
                    $nuevo_estado = 'agotado';
                } elseif ($stock_resultante <= $producto->stock_minimo) {
                    $nuevo_estado = 'stock_bajo';
                }

                $producto->update([
                    'stock_actual' => $stock_resultante,
                    'estado' => $nuevo_estado
                ]);
            }

            // Actualizar total de la venta
            $venta->update(['total' => $total]);

            DB::commit();

            return back()->with('success', '¡Compra simulada con éxito! Revisa el historial de ventas en el panel.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }
    }
}
