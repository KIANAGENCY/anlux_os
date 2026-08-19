<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        h1 { font-size: 16px; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #999; padding: 4px; vertical-align: top; }
        th { background: #e6eef7; }
    </style>
</head>
<body>
    <h1>Orden de servicio — {{ $orden['folio'] ?? '' }}</h1>
    <p><strong>Cliente:</strong> {{ $orden['nombre_cliente'] ?? '' }}</p>
    <p><strong>Teléfono:</strong> {{ $orden['telefono'] ?? '' }} &nbsp; <strong>Correo:</strong> {{ $orden['correo'] ?? '' }}</p>
    <p><strong>Dirección:</strong> {{ $orden['direccion'] ?? '' }}</p>
    <p><strong>Población:</strong> {{ $orden['poblacion'] ?? '' }} &nbsp; <strong>Estatus:</strong> {{ $orden['estatus'] ?? '' }}</p>

    <h2>Equipos</h2>
    <table>
        <tr><th>Marca</th><th>Modelo</th><th>Serie</th><th>Tipo</th><th>Falla</th></tr>
        @forelse($equipos as $eq)
            @php $e = (array) $eq; @endphp
            <tr>
                <td>{{ $e['marca'] ?? '' }}</td>
                <td>{{ $e['modelo'] ?? '' }}</td>
                <td>{{ $e['serie'] ?? '' }}</td>
                <td>{{ $e['tipo_servicio'] ?? '' }}</td>
                <td>{{ $e['descripcion_falla'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Sin equipos</td></tr>
        @endforelse
    </table>

    <h2>Trabajos</h2>
    <table>
        <tr><th>Clave</th><th>Descripción</th><th>Importe</th></tr>
        @forelse($trabajos as $t)
            @php $r = (array) $t; @endphp
            <tr>
                <td>{{ $r['clave'] ?? '' }}</td>
                <td>{{ $r['descripcion'] ?? '' }}</td>
                <td>${{ number_format((float)($r['importe'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Sin trabajos</td></tr>
        @endforelse
    </table>

    <h2>Materiales</h2>
    <table>
        <tr><th>Descripción</th><th>Cant</th><th>P.U.</th><th>Importe</th></tr>
        @forelse($materiales as $m)
            @php $r = (array) $m; @endphp
            <tr>
                <td>{{ $r['descripcion'] ?? '' }}</td>
                <td>{{ $r['cantidad'] ?? '' }}</td>
                <td>${{ number_format((float)($r['precio_unitario'] ?? 0), 2) }}</td>
                <td>${{ number_format((float)($r['importe'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Sin materiales</td></tr>
        @endforelse
    </table>

    <h2>Totales</h2>
    <p>Subtotal trabajos: ${{ number_format((float)($orden['subtotal_t'] ?? 0), 2) }}</p>
    <p>Subtotal materiales: ${{ number_format((float)($orden['subtotal_m'] ?? 0), 2) }}</p>
    <p>IVA: ${{ number_format((float)($orden['iva'] ?? 0), 2) }}</p>
    <p><strong>Total: ${{ number_format((float)($orden['total_pagar'] ?? 0), 2) }}</strong></p>
</body>
</html>
