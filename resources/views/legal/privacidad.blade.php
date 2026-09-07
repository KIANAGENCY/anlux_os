@extends('layouts.legal')

@section('content')
    @php
        $empresa = config('anlux.company_legal_name', 'La empresa titular del sistema');
        $producto = config('app.name', 'Anlux');
        $jurisdiccion = config('anlux.company_jurisdiction', 'México');
        $privacyEmail = config('anlux.privacy_email', 'privacidad@ejemplo.com');
    @endphp
    <h1>Aviso de privacidad</h1>
    <p class="legal-updated">Ultima actualizacion: {{ now()->format('d/m/Y') }}</p>

    <p>
        <strong>{{ $empresa }}</strong> (en adelante, &laquo;la Empresa&raquo;),
        con operaciones en <strong>{{ $jurisdiccion }}</strong>, es responsable del tratamiento de los
        datos personales recabados a traves del sistema <strong>{{ $producto }}</strong> y de los servicios relacionados,
        incluida la comunicacion por WhatsApp, conforme a la Ley Federal de Proteccion de Datos Personales
        en Posesion de los Particulares (LFPDPPP) y demas normativa aplicable.
    </p>

    <h2>1. Datos personales que recabamos</h2>
    <p>Podemos recabar, entre otros, los siguientes datos:</p>
    <ul>
        <li><strong>Identificacion y contacto:</strong> nombre, telefono, correo electronico.</li>
        <li><strong>Datos de orden de servicio:</strong> folio, equipo, descripcion del servicio, estatus, historial.</li>
        <li><strong>Datos de usuarios internos:</strong> nombre de tecnico, usuario de acceso, actividad en el sistema.</li>
        <li><strong>Comunicaciones:</strong> mensajes intercambiados por WhatsApp o correo vinculados a una orden.</li>
        <li><strong>Datos tecnicos:</strong> direccion IP, registros de acceso, cookies de sesion y logs de seguridad.</li>
    </ul>

    <h2>2. Finalidades del tratamiento</h2>
    <p>Utilizamos los datos personales para:</p>
    <ul>
        <li>Registrar, dar seguimiento y administrar ordenes de servicio.</li>
        <li>Comunicar al cliente el estatus de su orden (recepcion, terminado, entrega, etc.).</li>
        <li>Enviar notificaciones por correo electronico y WhatsApp cuando el cliente lo haya solicitado o autorizado.</li>
        <li>Administrar cuentas de personal autorizado y proteger la seguridad del sistema.</li>
        <li>Cumplir obligaciones legales y resolver controversias.</li>
    </ul>

    <h2>3. WhatsApp y Meta</h2>
    <p>
        Cuando utilizamos WhatsApp Business Cloud API, ciertos datos (numero telefonico, contenido del mensaje
        y metadatos de entrega) son procesados tambien por <strong>Meta Platforms, Inc.</strong> conforme a sus
        propias politicas. La Empresa utiliza esta herramienta unicamente para comunicacion relacionada con
        ordenes de servicio y soporte al cliente.
    </p>
    <p>
        URL del servicio: <strong>{{ config('app.url') }}</strong>
    </p>

    <h2>4. Transferencia de datos</h2>
    <p>
        Los datos pueden ser tratados por proveedores de hosting, correo electronico, WhatsApp/Meta y servicios
        tecnologicos necesarios para operar {{ $producto }}, siempre bajo obligaciones de confidencialidad y seguridad
        razonables. No vendemos datos personales a terceros.
    </p>

    <h2>5. Conservacion</h2>
    <p>
        Conservamos los datos durante el tiempo necesario para cumplir las finalidades descritas, atender
        obligaciones legales y resolver reclamaciones. Los registros de ordenes y comunicaciones pueden
        conservarse conforme a las politicas internas de la Empresa y la legislacion aplicable.
    </p>

    <h2>6. Derechos ARCO</h2>
    <p>
        Usted puede solicitar acceso, rectificacion, cancelacion u oposicion al tratamiento de sus datos personales,
        asi como revocar su consentimiento cuando proceda, enviando solicitud a
        <strong>{{ $privacyEmail }}</strong> o a los canales de contacto de la Empresa.
        Responderemos en los plazos establecidos por la ley.
    </p>

    <h2>7. Medidas de seguridad</h2>
    <p>
        Implementamos medidas administrativas, tecnicas y fisicas para proteger los datos personales contra
        acceso no autorizado, perdida o alteracion, incluyendo control de acceso, cifrado de datos sensibles
        en reposo cuando aplique, y registros de auditoria.
    </p>

    <h2>8. Cookies y sesion</h2>
    <p>
        El sistema utiliza cookies y mecanismos de sesion necesarios para autenticacion y seguridad.
        No utilizamos cookies con fines publicitarios de terceros.
    </p>

    <h2>9. Cambios al aviso</h2>
    <p>
        Cualquier modificacion a este aviso se publicara en esta misma pagina con la fecha de actualizacion
        correspondiente.
    </p>

    <h2>10. Contacto</h2>
    <p>
        Para ejercer sus derechos o aclaraciones sobre privacidad, contacte al administrador del sistema {{ $producto }}
        o a la Empresa a traves de sus canales oficiales ({{ $jurisdiccion }}).
    </p>
@endsection
