<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MovimientoInventarioController extends Controller
{
    public function index()
    {
        // 1. Entradas (solo tipo = 'entrada')
        $entradas = MovimientoInventario::with(['producto', 'user'])
            ->where('tipo', 'entrada')
            ->orderBy('fecha', 'desc')
            ->get();

        // 2. Salidas Manuales (tipo = 'salida' y venta_id = null)
        $salidas = MovimientoInventario::with(['producto', 'user'])
            ->where('tipo', 'salida')
            ->whereNull('venta_id')
            ->orderBy('fecha', 'desc')
            ->get();

        // 3. Ventas (Cargamos las ventas con su cliente y detalles de productos)
        $ventas = \App\Models\Venta::with(['cliente', 'detalles.producto'])
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();
            
        return Inertia::render('Movimientos/Index', [
            'entradas' => $entradas,
            'salidas' => $salidas,
            'ventas' => $ventas
        ]);
    }

    public function create()
    {
        // Solo productos activos
        $productos = Producto::where('estado', '!=', 'inactivo')->get();
        return Inertia::render('Movimientos/Create', [
            'productos' => $productos
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tipo' => 'required|in:entrada,salida',
            'cantidad' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $producto = Producto::lockForUpdate()->findOrFail($request->producto_id);
            $stock_anterior = $producto->stock_actual;
            $cantidad = $request->cantidad;

            if ($request->tipo === 'salida' && $stock_anterior < $cantidad) {
                return back()->withErrors(['cantidad' => 'No hay suficiente stock disponible para esta salida.']);
            }

            $stock_resultante = $request->tipo === 'entrada' 
                ? $stock_anterior + $cantidad 
                : $stock_anterior - $cantidad;

            // Registrar movimiento
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => $request->tipo,
                'cantidad' => $cantidad,
                'stock_anterior' => $stock_anterior,
                'stock_resultante' => $stock_resultante,
                'user_id' => auth()->id(),
            ]);

            // Determinar nuevo estado del producto
            $nuevo_estado = $producto->estado;
            if ($nuevo_estado !== 'inactivo') {
                if ($stock_resultante == 0) {
                    $nuevo_estado = 'agotado';
                } elseif ($stock_resultante <= $producto->stock_minimo) {
                    $nuevo_estado = 'stock_bajo';
                } else {
                    $nuevo_estado = 'disponible';
                }
            }

            // Actualizar stock del producto
            $producto->update([
                'stock_actual' => $stock_resultante,
                'estado' => $nuevo_estado
            ]);

            DB::commit();

            return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al registrar el movimiento.');
        }
    }

    public function edit(MovimientoInventario $movimiento)
    {
        if (auth()->user()->role_id !== 1) {
            abort(403, 'No autorizado.');
        }

        $productos = Producto::where('estado', '!=', 'inactivo')->get();
        return Inertia::render('Movimientos/Edit', [
            'movimiento' => $movimiento,
            'productos' => $productos
        ]);
    }

    public function update(Request $request, MovimientoInventario $movimiento)
    {
        if (auth()->user()->role_id !== 1) {
            abort(403, 'No autorizado.');
        }

        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tipo' => 'required|in:entrada,salida',
            'cantidad' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $producto = Producto::lockForUpdate()->findOrFail($request->producto_id);
            
            // 1. Revertir el movimiento anterior temporalmente
            $stock_temp = $producto->stock_actual;
            if ($movimiento->tipo === 'entrada') {
                $stock_temp -= $movimiento->cantidad;
            } else {
                $stock_temp += $movimiento->cantidad;
            }

            // 2. Validar que la nueva cantidad de salida sea factible
            $cantidad = $request->cantidad;
            if ($request->tipo === 'salida' && $stock_temp < $cantidad) {
                return back()->withErrors(['cantidad' => 'No hay suficiente stock disponible para realizar este cambio.']);
            }

            // 3. Calcular el nuevo stock resultante
            $stock_resultante = $request->tipo === 'entrada'
                ? $stock_temp + $cantidad
                : $stock_temp - $cantidad;

            // Actualizar el movimiento
            $movimiento->update([
                'producto_id' => $producto->id,
                'tipo' => $request->tipo,
                'cantidad' => $cantidad,
                'stock_anterior' => $stock_temp,
                'stock_resultante' => $stock_resultante,
            ]);

            // Determinar nuevo estado del producto
            $nuevo_estado = $producto->estado;
            if ($nuevo_estado !== 'inactivo') {
                if ($stock_resultante == 0) {
                    $nuevo_estado = 'agotado';
                } elseif ($stock_resultante <= $producto->stock_minimo) {
                    $nuevo_estado = 'stock_bajo';
                } else {
                    $nuevo_estado = 'disponible';
                }
            }

            // Actualizar stock del producto
            $producto->update([
                'stock_actual' => $stock_resultante,
                'estado' => $nuevo_estado
            ]);

            DB::commit();

            return redirect()->route('movimientos.index')->with('success', 'Movimiento actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al actualizar el movimiento: ' . $e->getMessage());
        }
    }

    public function destroy(MovimientoInventario $movimiento)
    {
        if (auth()->user()->role_id !== 1) {
            abort(403, 'No autorizado.');
        }

        try {
            DB::beginTransaction();

            $producto = Producto::lockForUpdate()->findOrFail($movimiento->producto_id);

            // Revertir stock del movimiento
            if ($movimiento->tipo === 'entrada') {
                $stock_resultante = $producto->stock_actual - $movimiento->cantidad;
                if ($stock_resultante < 0) {
                    $stock_resultante = 0; // Se permite borrar, el stock queda en 0
                }
            } else {
                $stock_resultante = $producto->stock_actual + $movimiento->cantidad;
            }

            // Determinar nuevo estado del producto
            $nuevo_estado = $producto->estado;
            if ($nuevo_estado !== 'inactivo') {
                if ($stock_resultante == 0) {
                    $nuevo_estado = 'agotado';
                } elseif ($stock_resultante <= $producto->stock_minimo) {
                    $nuevo_estado = 'stock_bajo';
                } else {
                    $nuevo_estado = 'disponible';
                }
            }

            $producto->update([
                'stock_actual' => $stock_resultante,
                'estado' => $nuevo_estado
            ]);

            $movimiento->delete();

            DB::commit();

            return redirect()->route('movimientos.index')->with('success', 'Movimiento eliminado y stock revertido.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al eliminar el movimiento: ' . $e->getMessage());
        }
    }
}
