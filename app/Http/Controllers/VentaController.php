<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
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
}
