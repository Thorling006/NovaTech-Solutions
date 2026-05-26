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
        // Se eliminan en cascada los productos asociados desde la base de datos

        $categoria->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada exitosamente.');
    }
}
