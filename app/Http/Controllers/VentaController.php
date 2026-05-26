<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with('cliente')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return Inertia::render('Ventas/Index', [
            'ventas' => $ventas
        ]);
    }

    public function show(Venta $venta)
    {
        $venta->load(['cliente', 'detalles.producto']);
        
        return Inertia::render('Ventas/Show', [
            'venta' => $venta
        ]);
    }

    public function edit(Venta $venta)
    {
        if (auth()->user()->role_id !== 1) {
            abort(403, 'No autorizado.');
        }

        $venta->load(['cliente', 'detalles.producto']);
        return Inertia::render('Ventas/Edit', [
            'venta' => $venta
        ]);
    }

    public function update(Request $request, Venta $venta)
    {
        if (auth()->user()->role_id !== 1) {
            abort(403, 'No autorizado.');
        }

        $request->validate([
            'cliente.nombre' => 'required|string|max:255',
            'cliente.correo' => 'required|email|max:255',
            'cliente.telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:500',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'horario_entrega' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Actualizar cliente
            $venta->cliente->update([
                'nombre' => $request->input('cliente.nombre'),
                'correo' => $request->input('cliente.correo'),
                'telefono' => $request->input('cliente.telefono'),
            ]);

            // Actualizar venta
            $venta->update([
                'direccion' => $request->input('direccion'),
                'latitud' => $request->input('latitud'),
                'longitud' => $request->input('longitud'),
                'horario_entrega' => $request->input('horario_entrega'),
            ]);

            DB::commit();

            return redirect()->route('movimientos.index', ['tab' => 'ventas'])->with('success', 'Venta actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al actualizar la venta: ' . $e->getMessage());
        }
    }

    public function destroy(Venta $venta)
    {
        if (auth()->user()->role_id !== 1) {
            abort(403, 'No autorizado.');
        }

        try {
            DB::beginTransaction();

            $venta->load('detalles.producto');

            // 1. Devolver el stock de todos los productos en la venta
            foreach ($venta->detalles as $detalle) {
                $producto = $detalle->producto;
                if ($producto) {
                    $stock_anterior = $producto->stock_actual;
                    $stock_resultante = $stock_anterior + $detalle->cantidad;

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
                }
            }

            // 2. Eliminar movimientos de inventario asociados
            \App\Models\MovimientoInventario::where('venta_id', $venta->id)->delete();

            // 3. Eliminar la venta (los detalles se eliminan en cascada gracias a cascadeOnDelete en base de datos)
            $venta->delete();

            DB::commit();

            return redirect()->route('movimientos.index', ['tab' => 'ventas'])->with('success', 'Venta eliminada e inventario restablecido correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al eliminar la venta: ' . $e->getMessage());
        }
    }
}
