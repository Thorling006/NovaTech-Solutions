<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')->get();
        return Inertia::render('Productos/Index', [
            'productos' => $productos
        ]);
    }

    public function create()
    {
        $categorias = Categoria::where('estado', true)->get();
        return Inertia::render('Productos/Create', [
            'categorias' => $categorias
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:255|unique:productos',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'precio' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $imagenPath = null;
        if ($request->hasFile('imagen')) {
            $imagenPath = $request->file('imagen')->store('productos', 'public');
        }

        Producto::create([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'categoria_id' => $request->categoria_id,
            'precio' => $request->precio,
            'stock_actual' => 0, // Inicia en 0, se carga con movimientos
            'stock_minimo' => $request->stock_minimo,
            'estado' => 'agotado', // Si inicia en 0, está agotado
            'imagen' => $imagenPath,
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::where('estado', true)->get();
        return Inertia::render('Productos/Edit', [
            'producto' => $producto,
            'categorias' => $categorias
        ]);
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'codigo' => 'required|string|max:255|unique:productos,codigo,'.$producto->id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'precio' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'imagen' => 'nullable|image|max:2048',
            'estado' => 'required|in:disponible,stock_bajo,agotado,inactivo',
        ]);

        $imagenPath = $producto->imagen;
        if ($request->hasFile('imagen')) {
            if ($imagenPath && Storage::disk('public')->exists($imagenPath)) {
                Storage::disk('public')->delete($imagenPath);
            }
            $imagenPath = $request->file('imagen')->store('productos', 'public');
        }

        // Determinar estado basado en el stock si no se envió manualmente o si se requiere automatismo
        // Por simplicidad, tomaremos el estado que envía el form, pero en movimientos se actualizará automático.
        
        $producto->update([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'categoria_id' => $request->categoria_id,
            'precio' => $request->precio,
            'stock_minimo' => $request->stock_minimo,
            'estado' => $request->estado,
            'imagen' => $imagenPath,
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto)
    {
        if ($producto->movimientos()->count() > 0) {
            return redirect()->route('productos.index')->with('error', 'No se puede eliminar el producto porque tiene movimientos de inventario. Puedes cambiar su estado a inactivo.');
        }

        if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
            Storage::disk('public')->delete($producto->imagen);
        }

        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
