<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::all();
        return Inertia::render('Categorias/Index', [
            'categorias' => $categorias
        ]);
    }

    public function create()
    {
        return Inertia::render('Categorias/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias',
            'estado' => 'boolean',
        ]);

        Categoria::create([
            'nombre' => $request->nombre,
            'estado' => $request->estado ?? true,
        ]);

        return redirect()->route('categorias.index')->with('success', 'Categoría creada exitosamente.');
    }

    public function edit(Categoria $categoria)
    {
        return Inertia::render('Categorias/Edit', [
            'categoria' => $categoria
        ]);
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre,'.$categoria->id,
            'estado' => 'boolean',
        ]);

        $categoria->update([
            'nombre' => $request->nombre,
            'estado' => $request->estado ?? true,
        ]);

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Categoria $categoria)
    {
        // Alternativamente solo desactivarla, pero el requerimiento dice "Desactivar categoría" en el front,
        // asumiendo que delete es eliminar y desactivar es cambiar estado.
        // Verificamos si tiene productos antes de eliminar
        if ($categoria->productos()->count() > 0) {
            return redirect()->route('categorias.index')->with('error', 'No se puede eliminar la categoría porque tiene productos asociados. Prueba desactivándola.');
        }

        $categoria->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada exitosamente.');
    }
}
