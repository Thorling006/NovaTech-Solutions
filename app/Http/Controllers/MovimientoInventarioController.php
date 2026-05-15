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
        $movimientos = MovimientoInventario::with(['producto', 'user'])
            ->orderBy('fecha', 'desc')
            ->get();
            
        return Inertia::render('Movimientos/Index', [
            'movimientos' => $movimientos
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
}
