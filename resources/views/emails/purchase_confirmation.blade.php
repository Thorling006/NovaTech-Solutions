<x-mail::message>
# ¡Gracias por tu compra en NovaTech!

Hola **{{ $venta->cliente->nombre }}**,

Hemos recibido tu pago correctamente. Adjunto a este correo encontrarás tu factura en formato PDF.

### Datos de tu compra:
* **Número de Venta (Ticket):** `{{ $venta->id }}`
* **Código de Seguimiento:** `{{ $venta->tracking_id }}`
* **Total Pagado:** ${{ number_ his ?? number_format($venta->total, 2) }}
* **Dirección de Entrega:** {{ $venta->direccion }}
* **Horario de Entrega Seleccionado:** {{ $venta->horario_entrega }}

Puedes realizar el seguimiento de tu pedido en tiempo real utilizando el siguiente enlace y tu código de seguimiento (`{{ $venta->tracking_id }}`):

<x-mail::button :url="route('tracking.index')">
Seguir mi pedido
</x-mail::button>

Gracias por confiar en nosotros,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
