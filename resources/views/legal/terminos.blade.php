@extends('layouts.legal')

@section('content')
    <h1>Terminos y condiciones</h1>
    <p class="legal-updated">Ultima actualizacion: {{ now()->format('d/m/Y') }}</p>

    <p>
        Los presentes terminos regulan el acceso y uso del sistema <strong>Exacto</strong>,
        plataforma interna para la gestion de ordenes de servicio, administracion operativa
        y comunicacion con clientes de <strong>Expertos en Administracion y en Computo, S.A. de C.V.</strong>
        (en adelante, &laquo;la Empresa&raquo;), disponible en
        <strong>{{ config('app.url') }}</strong>.
    </p>

    <h2>1. Aceptacion</h2>
    <p>
        Al acceder al sistema, iniciar sesion o utilizar cualquiera de sus modulos, el usuario
        acepta estos terminos y condiciones. Si no esta de acuerdo, debe abstenerse de usar la plataforma.
    </p>

    <h2>2. Uso autorizado</h2>
    <p>Exacto es de <strong>uso interno y profesional</strong>. Solo pueden utilizarlo:</p>
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

    <h2>4. Comunicaciones por WhatsApp</h2>
    <p>
        Las notificaciones y mensajes enviados por WhatsApp se realizan conforme a las politicas de
        Meta/WhatsApp Business y con consentimiento previo del cliente cuando corresponda.
        El personal autorizado debe utilizar el chat unicamente para fines de soporte y seguimiento
        vinculados a ordenes de servicio.
    </p>

    <h2>5. Responsabilidades del usuario</h2>
    <ul>
        <li>Mantener la confidencialidad de sus credenciales de acceso.</li>
        <li>Registrar informacion veraz y actualizada en las ordenes de servicio.</li>
        <li>No intentar vulnerar, copiar indebidamente o interferir con el funcionamiento del sistema.</li>
        <li>Cumplir la legislacion aplicable en materia de proteccion de datos personales.</li>
    </ul>

    <h2>6. Propiedad intelectual</h2>
    <p>
        El software, diseno, logotipos, bases de datos y contenidos del sistema son propiedad de la Empresa
        o de sus licenciantes. No se concede ninguna licencia de uso mas alla del acceso operativo autorizado.
    </p>

    <h2>7. Limitacion de responsabilidad</h2>
    <p>
        La Empresa procurara mantener la disponibilidad del sistema, pero no garantiza operacion ininterrumpida.
        No sera responsable por fallas de terceros (hosting, Meta/WhatsApp, proveedores de correo) ni por
        uso indebido del sistema por parte de usuarios no autorizados.
    </p>

    <h2>8. Modificaciones</h2>
    <p>
        La Empresa puede actualizar estos terminos en cualquier momento. Las versiones vigentes se publicaran
        en esta pagina. El uso continuado del sistema implica la aceptacion de los cambios.
    </p>

    <h2>9. Contacto</h2>
    <p>
        Para dudas sobre estos terminos puede contactarnos a traves de los canales oficiales de la Empresa
        o del administrador del sistema Exacto.
    </p>
@endsection
