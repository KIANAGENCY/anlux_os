@extends('layouts.legal')

@section('content')
    @php
        $empresa = config('anlux.company_legal_name', 'La empresa titular del sistema');
        $producto = config('app.name', 'Anlux');
    @endphp
    <h1>Terminos y condiciones</h1>
    <p class="legal-updated">Ultima actualizacion: {{ now()->format('d/m/Y') }}</p>

    <p>
        Los presentes terminos regulan el acceso y uso del sistema <strong>{{ $producto }}</strong>,
        plataforma para la gestion de ordenes de servicio, administracion operativa
        y comunicacion con clientes de <strong>{{ $empresa }}</strong>
        (en adelante, &laquo;la Empresa&raquo;), disponible en
        <strong>{{ config('app.url') }}</strong>.
    </p>

    <h2>1. Aceptacion</h2>
    <p>
        Al acceder al sistema, iniciar sesion o utilizar cualquiera de sus modulos, el usuario
        acepta estos terminos y condiciones. Si no esta de acuerdo, debe abstenerse de usar la plataforma.
    </p>

    <h2>2. Uso autorizado</h2>
    <p>{{ $producto }} es de <strong>uso interno y profesional</strong>. Solo pueden utilizarlo:</p>
    <ul>
        <li>Personal autorizado por la Empresa (tecnicos, administradores y cuentas registradas).</li>
        <li>Usuarios con credenciales validas emitidas por un administrador del sistema.</li>
    </ul>
    <p>
        Queda prohibido compartir contrasenas, permitir el acceso a terceros no autorizados o utilizar
        el sistema para fines distintos a las operaciones legitimas de la Empresa.
    </p>

    <h2>3. Servicios del sistema</h2>
    <p>Entre las funciones del sistema se incluyen, de manera enunciativa:</p>
    <ul>
        <li>Registro, consulta y seguimiento de ordenes de servicio.</li>
        <li>Generacion de documentos PDF relacionados con ordenes.</li>
        <li>Notificaciones a clientes por correo electronico y WhatsApp (cuando aplique).</li>
        <li>Administracion de usuarios, seguridad y catalogos internos.</li>
    </ul>

    <h2>4. Responsabilidades del usuario</h2>
    <p>El usuario se compromete a:</p>
    <ul>
        <li>Proporcionar informacion veraz y actualizada.</li>
        <li>Custodiar sus credenciales de acceso.</li>
        <li>Usar el sistema conforme a las politicas internas de la Empresa y la legislacion aplicable.</li>
    </ul>

    <h2>5. Disponibilidad y cambios</h2>
    <p>
        La Empresa puede modificar, suspender o interrumpir temporalmente el servicio por mantenimiento,
        mejoras o causas de fuerza mayor. Tambien puede actualizar estos terminos; la version vigente
        sera la publicada en esta pagina.
    </p>

    <h2>6. Limitacion de responsabilidad</h2>
    <p>
        En la medida permitida por la ley, la Empresa no sera responsable por danos indirectos,
        lucro cesante o perdida de datos derivados del uso o imposibilidad de uso del sistema,
        salvo dolo o negligencia grave.
    </p>

    <h2>7. Contacto</h2>
    <p>
        Para dudas sobre estos terminos, contacte al administrador de {{ $producto }} o a la Empresa
        a traves de los canales oficiales publicados en el sistema.
    </p>
@endsection
