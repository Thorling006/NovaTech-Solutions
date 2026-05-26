<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrackingController extends Controller
{
    public function index()
    {
        return Inertia::render('Tracking/Index');
    }

    public function search(Request $request)
    {
        $request->validate([
            'tracking_id' => 'required|string',
        ]);

        $venta = Venta::with(['conductor', 'detalles.producto'])
            ->where('tracking_id', $request->tracking_id)
            ->first();

        if (!$venta) {
            return back()->with('error', 'Código de seguimiento no encontrado.');
        }

        return Inertia::render('Tracking/Show', [
            'venta' => $venta
        ]);
    }
}
