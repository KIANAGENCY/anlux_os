@extends('layouts.legal')

@section('content')
    <h1>Eliminacion de datos del usuario</h1>
    <p class="legal-updated">Ultima actualizacion: {{ now()->format('d/m/Y') }}</p>

    <p>
        <strong>Expertos en Administracion y en Computo, S.A. de C.V.</strong> (en adelante, &laquo;la Empresa&raquo;),
        responsable del sistema <strong>Exacto</strong>, pone a tu disposicion este procedimiento para solicitar la
        eliminacion de tus datos personales, incluidos los datos asociados a las comunicaciones por WhatsApp.
    </p>

    <h2>1. Que datos se pueden eliminar</h2>
    <ul>
        <li>Nombre, telefono y correo electronico de contacto.</li>
        <li>Mensajes intercambiados por WhatsApp vinculados a una orden de servicio.</li>
        <li>Datos de contacto asociados a tus ordenes de servicio, salvo los que la Empresa deba conservar por
            obligacion legal o contable.</li>
    </ul>

    <h2>2. Como solicitar la eliminacion</h2>
    <p>Para solicitar la eliminacion de tus datos puedes hacerlo por cualquiera de estos medios:</p>
    <ul>
        <li>
            <strong>Correo electronico:</strong> envia tu solicitud a
            <strong>privacidad@exactolp.mx</strong> con el asunto &laquo;Eliminacion de datos&raquo;.
        </li>
        <li>
            <strong>WhatsApp:</strong> escribe al numero oficial de atencion de la Empresa indicando que deseas
            eliminar tus datos.
        </li>
    </ul>
    <p>
        Para identificar tu informacion, incluye en tu solicitud el nombre y el numero de telefono con el que te
        comunicaste, y si la tienes, el folio de tu orden de servicio.
    </p>

    <h2>3. Plazo de atencion</h2>
    <p>
        Atenderemos tu solicitud en un plazo razonable conforme a la legislacion aplicable. Una vez verificada tu
        identidad, eliminaremos o anonimizaremos los datos solicitados, salvo aquella informacion que debamos
        conservar por obligaciones legales, fiscales o para la resolucion de controversias.
    </p>

    <h2>4. Datos procesados por WhatsApp/Meta</h2>
    <p>
        Algunos datos de mensajeria son procesados por <strong>Meta Platforms, Inc.</strong> conforme a sus propias
        politicas. La eliminacion en nuestros sistemas no implica necesariamente la eliminacion en los sistemas de
        Meta, los cuales se rigen por sus propios terminos y plazos.
    </p>

    <h2>5. Contacto</h2>
    <p>
        Para cualquier duda sobre este procedimiento o el tratamiento de tus datos, consulta nuestro
        <a href="{{ route('legal.privacidad') }}">Aviso de privacidad</a> o contacta al administrador del sistema
        Exacto en Los Cabos, B.C.S., Mexico.
    </p>
@endsection
