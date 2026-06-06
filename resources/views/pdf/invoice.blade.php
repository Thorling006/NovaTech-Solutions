<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura - {{ $venta->id }}</title>
    <style>
        @page {
            margin: 0px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            margin: 0px;
            padding: 0px;
            background-color: #ffffff;
            font-size: 13px;
            line-height: 1.5;
        }
        .header-beige {
            background-color: #f3ede6;
            padding: 40px 50px 30px 50px;
            position: relative;
        }
        .invoice-title {
            font-size: 38px;
            font-weight: 300;
            letter-spacing: 2px;
            margin: 0;
            color: #000000;
        }
        .invoice-number-box {
            border: 1.5px solid #000000;
            display: inline-block;
            padding: 4px 15px;
            margin-top: 10px;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .logo-container {
            position: absolute;
            right: 50px;
            top: 35px;
            text-align: right;
        }
        .logo-letter {
            font-family: Georgia, serif;
            font-size: 60px;
            font-style: italic;
            line-height: 1;
            margin: 0;
            color: #000000;
            font-weight: normal;
        }
        .logo-underline {
            width: 70px;
            height: 1.5px;
            background-color: #000000;
            float: right;
            margin-top: -5px;
        }
        .content {
            padding: 40px 50px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .info-table td {
            width: 48%;
            vertical-align: top;
        }
        .info-table td.divider {
            width: 4%;
            border-right: 1px solid #cccccc;
        }
        .info-table td.right-side {
            padding-left: 30px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 15px;
            color: #000000;
        }
        .info-text {
            line-height: 1.6;
            color: #555555;
            font-size: 13px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            border: 1.5px solid #000000;
            border-left: none;
            border-right: none;
            padding: 8px 10px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: left;
        }
        .items-table th.num-col, .items-table td.num-col {
            text-align: right;
        }
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px dashed #e0e0e0;
            font-size: 13px;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .totals-divider {
            border-top: 1.5px solid #000000;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 13px;
        }
        .totals-table td.label-col {
            text-align: right;
            width: 75%;
        }
        .totals-table td.val-col {
            text-align: right;
            width: 25%;
        }
        .totals-table tr.grand-total td {
            border: 1.5px solid #000000;
            font-weight: bold;
            padding: 10px;
        }
        .totals-table tr.grand-total td.label-col {
            border-right: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: left;
        }
        .totals-table tr.grand-total td.val-col {
            border-left: none;
        }
        .payment-info-box {
            border: 1.5px solid #000000;
            padding: 15px;
            width: 45%;
            margin-top: 40px;
        }
        .payment-info-box h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer-beige {
            background-color: #f3ede6;
            text-align: center;
            padding: 15px 0;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="header-beige">
        <h1 class="invoice-title">FACTURA</h1>
        <div class="invoice-number-box">
            Nº: {{ $venta->id }}
        </div>
        <div class="logo-container">
            <div class="logo-letter">N</div>
            <div class="logo-underline"></div>
        </div>
    </div>

    <div class="content">
        <table class="info-table">
            <tr>
                <td>
                    <div class="section-title">Datos del Cliente</div>
                    <div class="info-text">
                        <strong>{{ $venta->cliente->nombre }}</strong><br>
                        {{ $venta->cliente->correo }}<br>
                        {{ $venta->cliente->telefono ?? 'S/T' }}<br>
                        {{ $venta->direccion }}
                    </div>
                </td>
                <td class="divider"></td>
                <td class="right-side">
                    <div class="section-title">Datos de la Empresa</div>
                    <div class="info-text">
                        <strong>NovaTech Solutions S.A. de C.V.</strong><br>
                        contacto@novatech.com.sv<br>
                        +503 2234-5678<br>
                        Alameda Roosevelt #102, San Salvador
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Detalle</th>
                    <th class="num-col" style="width: 15%;">Cantidad</th>
                    <th class="num-col" style="width: 20%;">Precio</th>
                    <th class="num-col" style="width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->producto->nombre }}</td>
                        <td class="num-col">{{ sprintf("%02d", $detalle->cantidad) }}</td>
                        <td class="num-col">${{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td class="num-col">${{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-divider"></div>

        <table class="totals-table">
            @if($venta->costo_envio > 0)
                <tr>
                    <td class="label-col">Envío</td>
                    <td class="val-col">${{ number_format($venta->costo_envio, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td class="label-col">TOTAL</td>
                <td class="val-col">${{ number_format($venta->total, 2) }}</td>
            </tr>
        </table>

        <div class="payment-info-box">
            <h4>Información de Pago</h4>
            <div class="info-text">
                @if(isset($payment_info) && $payment_info['metodo'] === 'card')
                    Tarjeta de Crédito/Débito<br>
                    Titular: {{ $payment_info['titular'] }}<br>
                    Tarjeta: **** **** **** {{ $payment_info['ultimos_cuatro'] }}
                @else
                    Efectivo / Contra Entrega
                @endif
                <br>Código Seguimiento: {{ $venta->tracking_id }}
            </div>
        </div>
    </div>

    <div class="footer-beige">
        WWW.NOVATECHSOLUTIONS.COM
    </div>

</body>
</html>
