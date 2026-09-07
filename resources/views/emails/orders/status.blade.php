@php
    $folio = trim((string) ($orden['folio'] ?? ''));
    $cliente = trim((string) ($orden['nombre_cliente'] ?? 'Cliente'));
    $equipos = is_array($orden['equipos'] ?? null) ? $orden['equipos'] : [];
    $totalPagar = (float) ($orden['total_pagar'] ?? 0);
    $brandColors = is_array($brandColors ?? null) ? $brandColors : [];
    $primary = $brandColors['primary'] ?? '#2563EB';
    $secondary = $brandColors['secondary'] ?? '#1E3A8A';
    $background = $brandColors['background'] ?? '#F2F6FF';
    $surface = $brandColors['surface'] ?? '#FFFFFF';
    $text = $brandColors['text'] ?? '#0F2544';
    $brandFont = $brandFont ?? 'Arial, Helvetica, sans-serif';
    $mensaje = match ($estatus) {
        'Terminado' => 'Su equipo está listo. Puede pasar a liquidar el saldo pendiente y recogerlo en nuestras instalaciones.',
        'Entregado' => 'Su equipo fue entregado correctamente. Gracias por confiar en Anlux.',
        default => 'Recibimos su equipo en recepción. Su comprobante de orden de servicio quedó registrado correctamente.',
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $folio !== '' ? $folio : 'Orden de servicio' }}</title>
</head>
<body style="margin:0; padding:0; background:{{ $background }}; font-family:{{ $brandFont }}; color:{{ $text }};">
    <div style="max-width:680px; margin:0 auto; padding:24px 12px;">
        <div style="background:{{ $surface }}; border:1px solid {{ $primary }}; border-radius:14px; overflow:hidden;">
            <div style="background:{{ $secondary }}; color:#ffffff; padding:18px 22px;">
                @if($logoSrc !== null)
                    <div style="margin:0 0 14px;">
                        <img
                            src="{{ $logoSrc }}"
                            alt="Anlux"
                            style="display:block; max-width:120px; height:auto;"
                        >
                    </div>
                @endif
                <h1 style="margin:0; font-size:22px;">Anlux - Orden de servicio</h1>
                @if($folio !== '')
                    <p style="margin:6px 0 0; font-size:15px;">Folio: <strong>{{ $folio }}</strong></p>
                @endif
            </div>

            <div style="padding:22px;">
                <p style="margin:0 0 14px; font-size:16px;">{{ $orden['saludo'] ?? 'Hola, qué tal' }}, {{ $cliente }}.</p>
                <p style="margin:0 0 20px; line-height:1.5; font-size:15px;">{{ $mensaje }}</p>

                <div style="border:1px solid #d6e2ff; border-radius:10px; padding:14px; margin-bottom:18px;">
                    <h2 style="margin:0 0 10px; font-size:16px; color:{{ $primary }};">Datos del cliente</h2>
                    <p style="margin:4px 0;"><strong>Cliente:</strong> {{ $cliente }}</p>
                    @if(!empty($orden['telefono']))
                        <p style="margin:4px 0;"><strong>Teléfono:</strong> {{ $orden['telefono'] }}</p>
                    @endif
                    @if(!empty($orden['poblacion']))
                        <p style="margin:4px 0;"><strong>Población/Ciudad:</strong> {{ $orden['poblacion'] }}</p>
                    @endif
                </div>

                <div style="border:1px solid #d6e2ff; border-radius:10px; padding:14px; margin-bottom:18px;">
                    <h2 style="margin:0 0 10px; font-size:16px; color:{{ $primary }};">Datos del equipo</h2>
                    @forelse($equipos as $index => $equipo)
                        <div style="padding:10px 0; border-top:{{ $index === 0 ? '0' : '1px solid #e7edff' }};">
                            <p style="margin:4px 0;"><strong>Equipo {{ $index + 1 }}:</strong>
                                {{ trim(($equipo['marca'] ?? '').' '.($equipo['modelo'] ?? '')) ?: 'Sin marca/modelo capturado' }}
                            </p>
                            @if(!empty($equipo['serie']))
                                <p style="margin:4px 0;"><strong>Serie:</strong> {{ $equipo['serie'] }}</p>
                            @endif
                            @if(!empty($equipo['tipo_servicio']))
                                <p style="margin:4px 0;"><strong>Tipo de servicio:</strong> {{ $equipo['tipo_servicio'] }}</p>
                            @endif
                            @if(!empty($equipo['descripcion_falla']))
                                <p style="margin:4px 0;"><strong>Falla reportada:</strong> {{ $equipo['descripcion_falla'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p style="margin:0;">No se capturaron datos del equipo.</p>
                    @endforelse
                </div>

                @if($estatus === 'Terminado')
                    <div style="background:#fff7e6; border:1px solid #ffd28a; border-radius:10px; padding:14px; margin-bottom:18px;">
                        <p style="margin:0;"><strong>Total registrado:</strong> ${{ number_format($totalPagar, 2) }}</p>
                    </div>
                @endif

                <p style="margin:20px 0 0; line-height:1.5; font-size:14px; color:#36506f;">
                    Este correo es una notificación automática de Anlux. Si tiene alguna duda, puede comunicarse con nosotros proporcionando su folio de orden.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
