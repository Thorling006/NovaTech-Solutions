<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Conductor;
use App\Models\RutaLogistica;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class RutaController extends Controller
{
    private $latBase = 13.348428;
    private $lngBase = -88.440182;

    private function calcularDistancia($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    public function index()
    {
        $ventas_pendientes = Venta::with(['cliente'])
            ->whereNull('ruta_logistica_id')
            ->whereIn('estado_envio', ['pendiente'])
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();
            
        $rutas = RutaLogistica::with(['conductor', 'ventas.cliente'])
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();

        $ventas_entregadas = Venta::with(['cliente', 'conductor'])
            ->where('estado_envio', 'entregado')
            ->orderBy('updated_at', 'desc')
            ->take(100)
            ->get();

        $conductores = Conductor::all();

        $ventas_nunca_entregadas = Venta::with(['cliente'])
            ->where('estado_envio', 'nunca_entregado')
            ->orderBy('updated_at', 'desc')
            ->take(100)
            ->get();

        return Inertia::render('Rutas/Index', [
            'ventas_pendientes' => $ventas_pendientes,
            'ventas_entregadas' => $ventas_entregadas,
            'ventas_nunca_entregadas' => $ventas_nunca_entregadas,
            'rutas' => $rutas,
            'conductores' => $conductores
        ]);
    }

    public function storeRuta(Request $request)
    {
        if ($request->input('conductor_id') === '') {
            $request->merge(['conductor_id' => null]);
        }

        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'conductor_id' => 'nullable|exists:conductores,id',
            'fecha_programada' => 'required|date',
            'hora_programada' => 'required|date_format:H:i',
            'ventas_ids' => 'required|array|min:1',
            'ventas_ids.*' => 'exists:ventas,id'
        ]);

        // Simular y validar horarios de entrega
        $ventas = Venta::with('cliente')->whereIn('id', $request->ventas_ids)->get()->keyBy('id');
        
        $lastLat = $this->latBase;
        $lastLng = $this->lngBase;
        $currentTime = \Carbon\Carbon::parse($request->hora_programada);

        foreach ($request->ventas_ids as $ventaId) {
            $venta = $ventas->get($ventaId);
            if (!$venta) continue;

            // 1. Distancia
            $distanciaKm = $this->calcularDistancia($lastLat, $lastLng, $venta->latitud, $venta->longitud);
            
            // 2. Tiempo de traslado (30 km/h = 2 min/km)
            $tiempoTrasladoMin = round($distanciaKm * 2);
            $currentTime->addMinutes($tiempoTrasladoMin);

            // 3. Validar horario
            $horario = $venta->horario_entrega;
            if ($horario && $horario !== 'flexible') {
                $horaHms = $currentTime->format('H:i:s');
                $valido = false;
                $mensajeHorario = '';

                if ($horario === 'morning') {
                    if ($horaHms >= '08:00:00' && $horaHms <= '12:00:00') $valido = true;
                    $mensajeHorario = 'Mañana (8:00 AM - 12:00 MD)';
                } elseif ($horario === 'afternoon') {
                    if ($horaHms >= '13:00:00' && $horaHms <= '17:00:00') $valido = true;
                    $mensajeHorario = 'Tarde (1:00 PM - 5:00 PM)';
                } elseif ($horario === 'evening') {
                    if ($horaHms >= '18:00:00' && $horaHms <= '21:00:00') $valido = true;
                    $mensajeHorario = 'Noche (6:00 PM - 9:00 PM)';
                } else {
                    $valido = true;
                }

                if (!$valido) {
                    $horaEstimada = $currentTime->format('h:i A');
                    $nombreCliente = $venta->cliente ? $venta->cliente->nombre : 'Desconocido';
                    return back()->withErrors([
                        'error' => "El pedido #{$venta->id} para el cliente '{$nombreCliente}' no se puede entregar en su horario de preferencia. Horario solicitado: {$mensajeHorario}. Hora estimada de llegada: {$horaEstimada}."
                    ]);
                }
            }

            // 4. Tiempo de entrega (15 minutos)
            $currentTime->addMinutes(15);

            // 5. Siguiente punto de partida
            $lastLat = $venta->latitud;
            $lastLng = $venta->longitud;
        }

        try {
            DB::beginTransaction();

            $codigo_unico = 'RUT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $nombre_final = $request->nombre ? $codigo_unico . ' | ' . $request->nombre : $codigo_unico;

            $ruta = RutaLogistica::create([
                'nombre' => $nombre_final,
                'conductor_id' => $request->conductor_id,
                'fecha_programada' => $request->fecha_programada,
                'hora_programada' => $request->hora_programada,
                'estado' => 'creada'
            ]);

            // Obtener ventas y respetar estrictamente el orden enviado por el cliente
            $orden = 1;
            foreach ($request->ventas_ids as $ventaId) {
                // Actualizar la venta con su orden manual en la ruta
                Venta::where('id', $ventaId)->update([
                    'ruta_logistica_id' => $ruta->id,
                    'orden_ruta' => $orden,
                    'conductor_id' => $request->conductor_id,
                    'estado_envio' => 'en_ruta',
                    'estado_entrega_geocerca' => 'pendiente',
                    'dia_entrega_asignado' => $request->fecha_programada,
                    'hora_entrega_asignada' => $request->hora_programada
                ]);
                $orden++;
            }

            // Marcar al conductor como ocupado si se asignó uno
            if ($request->conductor_id) {
                Conductor::where('id', $request->conductor_id)->update(['estado' => 'ocupado']);
            }

            DB::commit();

            return back()->with('success', 'Ruta creada con el orden manual asignado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroyRuta(RutaLogistica $ruta)
    {
        if ($ruta->estado === 'finalizada') {
            // Si es historial, solo desligar, mantener su estado entregado/fallido
            Venta::where('ruta_logistica_id', $ruta->id)->update([
                'ruta_logistica_id' => null,
                'orden_ruta' => null
            ]);
        } else {
            // Liberar ventas y devolverlas a pendientes si no estaba finalizada
            Venta::where('ruta_logistica_id', $ruta->id)->update([
                'ruta_logistica_id' => null,
                'orden_ruta' => null,
                'conductor_id' => null,
                'estado_envio' => 'pendiente',
                'estado_entrega_geocerca' => 'pendiente'
            ]);
            
            // Liberar conductor si estaba ocupado en esta ruta
            if ($ruta->conductor_id && $ruta->estado === 'en_curso') {
                Conductor::where('id', $ruta->conductor_id)->update(['estado' => 'disponible']);
            }
        }

        $ruta->delete();
        return back()->with('success', 'Ruta eliminada correctamente del registro.');
    }
}
