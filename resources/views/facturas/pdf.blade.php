<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $factura->idfactura }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1565c0; }
        .header p { margin: 0; font-size: 12px; color: #666; }

        .section { margin-bottom: 15px; }
        .section h3 { margin-bottom: 5px; color: #1565c0; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }

        .text-right { text-align: right; }
        .total { font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>Factura</h1>
        <p>Emitida el {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Datos del cliente -->
    <div class="section">
        <h3>Datos del Cliente</h3>
        <p>
            <strong>A nombre de:</strong>
            {{ $factura->nombre_factura ?? ($factura->usuario->nombres . ' ' . $factura->usuario->apellidos) }}
        </p>
        @if($factura->nit)
            <p><strong>NIT:</strong> {{ $factura->nit }}</p>
        @endif
        @if($factura->razon_social)
            <p><strong>Razón Social:</strong> {{ $factura->razon_social }}</p>
        @endif
    </div>

    <!-- Datos del plan -->
    <div class="section">
        <h3>Suscripción</h3>
        <table>
            <tr>
                <th>Plan</th>
                <th>Duración</th>
                <th>Precio</th>
            </tr>
            <tr>
                <td>{{ $factura->plan->nombre ?? $factura->suscripcion->plan->nombre ?? '—' }}</td>
                <td>{{ $factura->plan->duracion ?? $factura->suscripcion->plan->duracion ?? '—' }} meses</td>
                <td class="text-right">Bs. {{ number_format($factura->plan->precio ?? $factura->suscripcion->plan->precio ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Detalles de pago -->
    <div class="section">
        <h3>Detalles de Pago</h3>
        <table>
            <tr>
                <th>Método de pago</th>
                <th>Estado</th>
                <th>Total</th>
            </tr>
            <tr>
                <td>{{ $factura->tipoPago->nombre ?? '—' }}</td>
                <td>{{ ucfirst($factura->estado) }}</td>
                <td class="text-right total">Bs. {{ number_format($factura->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Pie -->
    <div class="section" style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
        <p>Gracias por tu compra</p>
        <p>Este documento es generado automáticamente y no requiere firma.</p>
    </div>
</body>
</html>
