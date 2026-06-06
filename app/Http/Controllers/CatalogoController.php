<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;

class CatalogoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')
            ->whereIn('estado', ['disponible', 'stock_bajo'])
            ->get();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'productos' => $productos
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'cliente.nombre' => 'required|string|max:255',
            'cliente.correo' => 'required|email|max:255',
            'cliente.telefono' => 'required|string|max:20',
            'metodo_pago' => 'required|string|in:cash,card',
            'tarjeta.numero' => 'required_if:metodo_pago,card|nullable|string|min:12|max:19',
            'tarjeta.titular' => 'required_if:metodo_pago,card|nullable|string|max:255',
            'tarjeta.expiracion' => 'required_if:metodo_pago,card|nullable|string|min:4|max:5',
            'tarjeta.cvv' => 'required_if:metodo_pago,card|nullable|string|digits_between:3,4',
            'direccion' => 'required|string|max:500',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'horario_entrega' => 'required|string|max:255',
            'carrito' => 'required|array|min:1',
            'carrito.*.producto_id' => 'required|exists:productos,id',
            'carrito.*.cantidad' => 'required|integer|min:1',
        ]);

        // Procesamiento de pago inmediato
        try {
            DB::beginTransaction();

            // 1. Obtener o crear cliente
            $cliente = Cliente::firstOrCreate(
                ['correo' => $request->input('cliente.correo')],
                [
                    'nombre' => $request->input('cliente.nombre'),
                    'telefono' => $request->input('cliente.telefono'),
                ]
            );

            // Calcular tarifa de envío (base: 13.348428, -88.440182)
            $latBase = 13.348428;
            $lngBase = -88.440182;
            $latTarget = $request->input('latitud');
            $lngTarget = $request->input('longitud');

            $earthRadius = 6371; // radio de la tierra en km
            $dLat = deg2rad($latTarget - $latBase);
            $dLng = deg2rad($lngTarget - $lngBase);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latBase)) * cos(deg2rad($latTarget)) * sin($dLng/2) * sin($dLng/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distanciaKm = $earthRadius * $c;

            $costo_envio = 0;
            if ($distanciaKm <= 5) {
                $costo_envio = 2.00;
            } elseif ($distanciaKm <= 20) {
                $costo_envio = 3.50;
            } elseif ($distanciaKm <= 50) {
                $costo_envio = 5.00;
            } else {
                $costo_envio = 5.00 + (($distanciaKm - 50) * 0.05);
            }

            // 2. Crear Venta
            $venta = Venta::create([
                'cliente_id' => $cliente->id,
                'total' => 0, // Se calculará ahora
                'costo_envio' => $costo_envio,
                'estado_envio' => 'pendiente',
                'estado' => 'completada',
                'direccion' => $request->input('direccion'),
                'latitud' => $request->input('latitud'),
                'longitud' => $request->input('longitud'),
                'horario_entrega' => $request->input('horario_entrega'),
            ]);

            $total = 0;
            $productosBajoStock = [];

            // Obtener el ID del SuperAdmin o un usuario genérico para registrar el movimiento
            $adminUser = User::where('role_id', 1)->first();

            // 3. Procesar carrito
            foreach ($request->carrito as $item) {
                $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);
                
                if ($producto->stock_actual < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
                }

                $subtotal = $producto->precio * $item['cantidad'];
                $total += $subtotal;

                // Crear detalle de venta
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal,
                ]);

                $stock_anterior = $producto->stock_actual;
                $stock_resultante = $stock_anterior - $item['cantidad'];

                // Crear movimiento de salida
                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'tipo' => 'salida',
                    'cantidad' => $item['cantidad'],
                    'stock_anterior' => $stock_anterior,
                    'stock_resultante' => $stock_resultante,
                    'user_id' => $adminUser->id ?? 1, // Usuario por defecto del sistema
                    'venta_id' => $venta->id,
                ]);

                // Actualizar producto
                $nuevo_estado = $producto->estado;
                $shouldSendAlert = false;

                if ($stock_resultante == 0) {
                    $nuevo_estado = 'agotado';
                } elseif ($stock_resultante <= $producto->stock_minimo) {
                    $nuevo_estado = 'stock_bajo';
                    // Si el stock actual antes de la compra era mayor al stock mínimo
                    // o si la alerta no ha sido enviada aún.
                    if (!$producto->stock_alert_sent) {
                        $shouldSendAlert = true;
                    }
                }

                $updateData = [
                    'stock_actual' => $stock_resultante,
                    'estado' => $nuevo_estado
                ];

                if ($shouldSendAlert) {
                    $updateData['stock_alert_sent'] = true;
                }

                $producto->update($updateData);

                if ($shouldSendAlert) {
                    // Guardamos la referencia para enviar después del commit exitoso
                    $productosBajoStock[] = $producto;
                }
            }

            // Actualizar total de la venta (Subtotal productos + costo envío)
            $venta->update(['total' => $total + $costo_envio]);

            // Refrescar el modelo para asegurar tener el tracking_id generado
            $venta->refresh();

            // Preparar información de pago para la factura
            $paymentInfo = [
                'metodo' => $request->metodo_pago
            ];

            if ($request->metodo_pago === 'card') {
                $num = $request->input('tarjeta.numero');
                $paymentInfo['titular'] = $request->input('tarjeta.titular');
                $paymentInfo['ultimos_cuatro'] = substr($num, -4);
            }

            // Generar PDF de la factura
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
                'venta' => $venta,
                'payment_info' => $paymentInfo
            ]);
            $pdfContent = $pdf->output();
            $pdfFileName = 'factura-' . $venta->id . '.pdf';

            // Guardar factura temporalmente para que pueda ser descargada de inmediato si se desea
            \Illuminate\Support\Facades\Storage::disk('public')->put('facturas/' . $pdfFileName, $pdfContent);

            DB::commit();

            // Enviar correo de confirmación al cliente con el PDF adjunto
            try {
                \Illuminate\Support\Facades\Mail::to($cliente->correo)
                    ->send(new \App\Mail\PurchaseConfirmation($venta, $pdfContent, $pdfFileName));
            } catch (\Exception $mailEx) {
                \Illuminate\Support\Facades\Log::error('Error al enviar correo de confirmación de compra: ' . $mailEx->getMessage());
            }

            // Enviar alertas de stock bajo urgentes a antonikevin308@gmail.com
            foreach ($productosBajoStock as $prod) {
                try {
                    \Illuminate\Support\Facades\Mail::to('antoniokevin308@gmail.com')
                        ->send(new \App\Mail\LowStockAlert($prod));
                } catch (\Exception $stockMailEx) {
                    \Illuminate\Support\Facades\Log::error('Error al enviar correo de alerta de stock para ' . $prod->nombre . ': ' . $stockMailEx->getMessage());
                }
            }

            return back()->with([
                'success' => '¡Compra completada con éxito!',
                'tracking_id' => $venta->tracking_id,
                'pdf_url' => asset('storage/facturas/' . $pdfFileName)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }
    }

    public function downloadInvoice($id)
    {
        $venta = Venta::with(['cliente', 'detalles.producto'])->findOrFail($id);
        
        // Simular o buscar si se pagó con tarjeta para mostrar últimos 4 dígitos
        // Como no guardamos la tarjeta en BD, mostramos efectivo por defecto si se descarga después,
        // o si es la sesión actual.
        $paymentInfo = [
            'metodo' => 'cash'
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
            'venta' => $venta,
            'payment_info' => $paymentInfo
        ]);

        return $pdf->download('factura-' . $venta->id . '.pdf');
    }
}
