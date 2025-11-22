<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura - Pago a Profesor #{{ $factura->idfactura }}</title>
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
        <h1>Factura - Pago a Profesor</h1>
        <p>Emitida el {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Profesor -->
    <div class="section">
        <h3>Datos del Profesor</h3>
        <p>
            <strong>Nombre:</strong>
            {{ $factura->pagoProfesor->profesor->usuario->nombres ?? '—' }}
            {{ $factura->pagoProfesor->profesor->usuario->apellidos ?? '' }}
        </p>
        <p>
            <strong>Curso:</strong>
            {{ $factura->pagoProfesor->licencia->curso->nombre ?? '—' }}
        </p>
    </div>

    <!-- Detalles del pago -->
    <div class="section">
        <h3>Detalles del Pago</h3>
        <table>
            <tr>
                <th>Método de Pago</th>
                <th>Referencia</th>
                <th>Monto</th>
                <th>Estado</th>
            </tr>
            <tr>
                <td>{{ ucfirst($factura->pagoProfesor->metodo_pago ?? '—') }}</td>
                <td>{{ $factura->pagoProfesor->referencia ?? $factura->referencia ?? '—' }}</td>
                <td class="text-right total">Bs. {{ number_format($factura->total, 2) }}</td>
                <td>{{ ucfirst($factura->estado ?? 'Pagada') }}</td>
            </tr>
        </table>
    </div>

    <!-- Pie -->
    <div class="section" style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
        <p>Gracias por su dedicación a la enseñanza</p>
        <p>Documento generado automáticamente — no requiere firma.</p>
    </div>
</body>
</html>
