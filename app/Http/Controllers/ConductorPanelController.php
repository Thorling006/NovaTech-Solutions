<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use App\Models\Conductor;
use App\Models\RutaLogistica;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ConductorPanelController extends Controller
{
    private function calcularDistancia($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c; // Devuelve en kilómetros
    }

    public function index(Request $request)
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();
        
        if (!$conductor) {
            $conductor = Conductor::create([
                'user_id' => $request->user()->id,
                'nombre' => $request->user()->name,
                'estado' => 'disponible',
                'vehiculo_tipo' => 'moto',
                'foto_url' => 'https://ui-avatars.com/api/?name='.urlencode($request->user()->name).'&background=3f3f46&color=fff',
                'latitud_actual' => 13.348428,
                'longitud_actual' => -88.440182
            ]);
        }

        // Obtener solo las rutas asignadas al conductor que no estén finalizadas
        $rutas = RutaLogistica::with(['ventas' => function($query) {
            $query->orderBy('orden_ruta', 'asc')->with('cliente');
        }])
        ->where('conductor_id', $conductor->id)
        ->whereIn('estado', ['creada', 'en_curso', 'retorno'])
        ->get();

        return Inertia::render('Conductor/Dashboard', [
            'conductor' => $conductor,
            'rutas' => $rutas
        ]);
    }

    public function iniciarRuta(Request $request, RutaLogistica $ruta)
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->firstOrFail();
        
        if ($ruta->conductor_id !== $conductor->id) abort(403);

        DB::transaction(function() use ($ruta, $conductor) {
            $ruta->update(['estado' => 'en_curso']);
            $conductor->update(['estado' => 'ocupado']);
        });

        return back()->with('success', 'Ruta iniciada. Dirígete al Almacén Base para cargar los pedidos.');
    }

    public function llegarAlAlmacen(Request $request, RutaLogistica $ruta)
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->firstOrFail();
        
        if ($ruta->conductor_id !== $conductor->id) abort(403);
        if ($ruta->estado !== 'en_curso') abort(400, 'La ruta no está en curso.');
        if ($ruta->llegada_almacen_inicial !== null) {
            return back()->withErrors(['geocerca' => 'Ya has registrado la llegada inicial al almacén.']);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric'
        ]);

        $distanciaKm = $this->calcularDistancia($request->lat, $request->lng, 13.348428, -88.440182);

        // Geocerca de 300 metros (0.3 km)
        if ($distanciaKm > 0.3) {
            return back()->withErrors(['geocerca' => 'Debes estar físicamente en el almacén para cargar pedidos. Distancia actual: ' . round($distanciaKm * 1000) . ' metros.']);
        }

        DB::transaction(function() use ($ruta, $request) {
            $ruta->update([
                'llegada_almacen_inicial' => now()
            ]);

            // Poner el primer punto (pedido) de entrega en camino
            $primeraVenta = $ruta->ventas()->orderBy('orden_ruta', 'asc')->first();
            if ($primeraVenta) {
                $primeraVenta->update(['estado_entrega_geocerca' => 'en_camino']);
            }
        });

        return back()->with('success', '¡Entrada y carga registradas en el almacén! Iniciando itinerario de entregas.');
    }

    public function llegarAlPunto(Request $request, Venta $venta)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric'
        ]);

        $distanciaKm = $this->calcularDistancia($request->lat, $request->lng, $venta->latitud, $venta->longitud);
        
        // Geocerca de 300 metros (0.3 km)
        if ($distanciaKm > 0.3) {
            return back()->withErrors(['geocerca' => 'Estás a ' . round($distanciaKm * 1000) . ' metros del cliente. Acércate a menos de 300 metros para registrar llegada.']);
        }

        $venta->update(['estado_entrega_geocerca' => 'en_el_punto']);
        
        // Update driver's last known location
        Conductor::where('id', $venta->conductor_id)->update([
            'latitud_actual' => $request->lat,
            'longitud_actual' => $request->lng
        ]);

        return back()->with('success', 'Llegada registrada exitosamente.');
    }

    public function finalizarPunto(Request $request, Venta $venta)
    {
        $request->validate([
            'resultado' => 'required|in:entregado,fallido'
        ]);

        if ($venta->estado_entrega_geocerca !== 'en_el_punto') {
            return back()->withErrors(['geocerca' => 'Primero debes marcar "Ya llegué al punto" mediante validación GPS.']);
        }

        DB::transaction(function() use ($request, $venta) {
            $esFallido = $request->resultado === 'fallido';
            $intentos = $esFallido ? $venta->intentos_entrega + 1 : $venta->intentos_entrega;
            
            $nuevoEstadoEnvio = 'pendiente';
            $nuevoRutaId = $venta->ruta_logistica_id;

            if ($request->resultado === 'entregado') {
                $nuevoEstadoEnvio = 'entregado';
            } elseif ($esFallido) {
                if ($intentos >= 3) {
                    $nuevoEstadoEnvio = 'nunca_entregado';
                } else {
                    $nuevoEstadoEnvio = 'pendiente';
                }
                // Si falla, se desliga de la ruta para que vuelva a bandeja de entrada (o nunca retirados)
                $nuevoRutaId = null; 
            }

            $venta->update([
                'estado_envio' => $nuevoEstadoEnvio,
                'estado_entrega_geocerca' => $request->resultado,
                'intentos_entrega' => $intentos,
                'ruta_logistica_id' => $nuevoRutaId,
                'orden_ruta' => $nuevoRutaId === null ? null : $venta->orden_ruta
            ]);

            // Buscar el siguiente punto pendiente en la ruta
            $siguienteVenta = Venta::where('ruta_logistica_id', $venta->ruta_logistica_id)
                ->where('orden_ruta', '>', $venta->orden_ruta)
                ->where('estado_entrega_geocerca', 'pendiente')
                ->orderBy('orden_ruta', 'asc')
                ->first();

            if ($siguienteVenta) {
                $siguienteVenta->update(['estado_entrega_geocerca' => 'en_camino']);
            } else {
                // Si no hay siguiente, poner la ruta en retorno al almacén (no finalizar automáticamente)
                RutaLogistica::where('id', $venta->ruta_logistica_id)->update(['estado' => 'retorno']);
            }
        });

        return back()->with('success', 'Punto finalizado. Avanzando en la ruta...');
    }

    public function finalizarRuta(Request $request, RutaLogistica $ruta)
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->firstOrFail();
        
        if ($ruta->conductor_id !== $conductor->id) abort(403);
        if ($ruta->estado !== 'retorno') {
            return back()->withErrors(['geocerca' => 'La ruta no se encuentra lista para finalizar o ya ha sido completada.']);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric'
        ]);

        $distanciaKm = $this->calcularDistancia($request->lat, $request->lng, 13.348428, -88.440182);

        // Geocerca de 300 metros (0.3 km)
        if ($distanciaKm > 0.3) {
            return back()->withErrors(['geocerca' => 'Debes estar físicamente en el almacén para marcar salida (finalizar entrega). Distancia: ' . round($distanciaKm * 1000) . ' metros.']);
        }

        DB::transaction(function() use ($ruta, $conductor, $request) {
            $ruta->update(['estado' => 'finalizada']);
            $conductor->update([
                'estado' => 'disponible',
                'latitud_actual' => $request->lat,
                'longitud_actual' => $request->lng
            ]);
        });

        return back()->with('success', '¡Salida registrada con éxito! Has finalizado la ruta en el almacén.');
    }

    public function updateVehicle(Request $request)
    {
        $request->validate(['vehiculo_tipo' => 'required|in:moto,carro,camion']);
        Conductor::where('user_id', $request->user()->id)->update(['vehiculo_tipo' => $request->vehiculo_tipo]);
        return back()->with('success', 'Vehículo actualizado.');
    }

    public function cancelarRuta(Request $request, RutaLogistica $ruta)
    {
        // Limpiar foto si no es un archivo (Inertia suele serializar null como string o vacio)
        if ($request->has('foto') && !$request->hasFile('foto')) {
            $request->offsetUnset('foto');
        }

        $request->validate([
            'motivo' => 'required|string|max:1000',
            'foto' => 'nullable|image|max:5120' // max 5MB, optional
        ]);

        if (!in_array($ruta->estado, ['en_curso', 'retorno'])) {
            return back()->withErrors(['ruta' => 'Solo puedes cancelar una ruta que está en curso o en retorno.']);
        }

        DB::beginTransaction();

        try {
            $path = null;
            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('cancelaciones_rutas', 'public');
            }

            // Actualizar ruta a cancelada
            $ruta->update([
                'estado' => 'cancelada',
                'motivo_cancelacion' => $request->motivo,
                'foto_cancelacion' => $path
            ]);

            // Liberar pedidos pendientes o en camino
            Venta::where('ruta_logistica_id', $ruta->id)
                ->whereIn('estado_entrega_geocerca', ['pendiente', 'en_camino'])
                ->update([
                    'ruta_logistica_id' => null,
                    'conductor_id' => null,
                    'estado_envio' => 'pendiente',
                    'estado_entrega_geocerca' => 'pendiente',
                    'orden_ruta' => null,
                    'dia_entrega_asignado' => null,
                    'hora_entrega_asignada' => null
                ]);

            // Liberar al conductor
            $conductor = Conductor::where('user_id', $request->user()->id)->first();
            if ($conductor) {
                $conductor->update(['estado' => 'disponible']);
            }

            DB::commit();

            return back()->with('success', 'Ruta cancelada de emergencia. Los pedidos han vuelto a la central.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Error al cancelar la ruta: ' . $e->getMessage()]);
        }
    }
}
