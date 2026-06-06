<x-mail::message>
# 🚨 ALERTA URGENTE: ¡Producto llegando al límite mínimo de stock!

El siguiente producto ha alcanzado o se encuentra por debajo de su nivel mínimo de stock definido:

<div style="text-align: center; margin-bottom: 20px;">
@if($producto->imagen)
    <img src="{{ url('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}" style="max-width: 200px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" />
@else
    <div style="background: #f3f4f6; color: #9ca3af; padding: 20px; border-radius: 8px; display: inline-block; width: 150px;">Sin imagen</div>
@endif
</div>

### Detalles del Producto:
* **Código:** `{{ $producto->codigo }}`
* **Nombre:** **{{ $producto->nombre }}**
* **Categoría:** {{ $producto->categoria->nombre ?? 'N/A' }}
* **Stock Actual:** <span style="color: #dc2626; font-weight: bold;">{{ $producto->stock_actual }}</span> unidades
* **Stock Mínimo:** {{ $producto->stock_minimo }} unidades
* **Precio:** ${{ number_format($producto->precio, 2) }}

### Especificación / Descripción:
{{ $producto->descripcion }}

---
*Esta es una notificación automática del sistema de inventario de NovaTech. Por favor, realiza el reabastecimiento correspondiente.*

<x-mail::button :url="route('productos.edit', $producto->id)">
Gestionar Producto en Panel
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
