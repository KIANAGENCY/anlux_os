<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class LegalController extends Controller
{
    public function terminos(): View
    {
        $empresa = e((string) config('anlux.company_legal_name', 'La empresa titular del sistema'));
        $producto = e((string) config('app.name', 'Anlux'));
        $appUrl = e((string) config('app.url'));

        return $this->renderLegal(
            'Terminos y condiciones',
            'Terminos y condiciones de uso del sistema Anlux para gestion de ordenes de servicio.',
            [
                ['type' => 'p', 'html' => "Los presentes terminos regulan el acceso y uso del sistema <strong>{$producto}</strong>, plataforma para la gestion de ordenes de servicio, administracion operativa y comunicacion con clientes de <strong>{$empresa}</strong> (en adelante, &laquo;la Empresa&raquo;), disponible en <strong>{$appUrl}</strong>."],
                ['type' => 'h2', 'text' => '1. Aceptacion'],
                ['type' => 'p', 'html' => 'Al acceder al sistema, iniciar sesion o utilizar cualquiera de sus modulos, el usuario acepta estos terminos y condiciones. Si no esta de acuerdo, debe abstenerse de usar la plataforma.'],
                ['type' => 'h2', 'text' => '2. Uso autorizado'],
                ['type' => 'p', 'html' => "{$producto} es de <strong>uso interno y profesional</strong>. Solo pueden utilizarlo:"],
                ['type' => 'ul', 'items' => [
                    'Personal autorizado por la Empresa (tecnicos, administradores y cuentas registradas).',
                    'Usuarios con credenciales validas emitidas por un administrador del sistema.',
                ]],
                ['type' => 'p', 'html' => 'Queda prohibido compartir contrasenas, permitir el acceso a terceros no autorizados o utilizar el sistema para fines distintos a las operaciones legitimas de la Empresa.'],
                ['type' => 'h2', 'text' => '3. Servicios del sistema'],
                ['type' => 'p', 'html' => 'Entre las funciones del sistema se incluyen, de manera enunciativa:'],
                ['type' => 'ul', 'items' => [
                    'Registro, consulta y seguimiento de ordenes de servicio.',
                    'Generacion de documentos PDF relacionados con ordenes.',
                    'Notificaciones a clientes por correo electronico y WhatsApp (cuando aplique).',
                    'Administracion de usuarios, seguridad y catalogos internos.',
                ]],
                ['type' => 'h2', 'text' => '4. Responsabilidades del usuario'],
                ['type' => 'p', 'html' => 'El usuario se compromete a:'],
                ['type' => 'ul', 'items' => [
                    'Proporcionar informacion veraz y actualizada.',
                    'Custodiar sus credenciales de acceso.',
                    'Usar el sistema conforme a las politicas internas de la Empresa y la legislacion aplicable.',
                ]],
                ['type' => 'h2', 'text' => '5. Disponibilidad y cambios'],
                ['type' => 'p', 'html' => 'La Empresa puede modificar, suspender o interrumpir temporalmente el servicio por mantenimiento, mejoras o causas de fuerza mayor. Tambien puede actualizar estos terminos; la version vigente sera la publicada en esta pagina.'],
                ['type' => 'h2', 'text' => '6. Limitacion de responsabilidad'],
                ['type' => 'p', 'html' => 'En la medida permitida por la ley, la Empresa no sera responsable por danos indirectos, lucro cesante o perdida de datos derivados del uso o imposibilidad de uso del sistema, salvo dolo o negligencia grave.'],
                ['type' => 'h2', 'text' => '7. Contacto'],
                ['type' => 'p', 'html' => "Para dudas sobre estos terminos, contacte al administrador de {$producto} o a la Empresa a traves de los canales oficiales publicados en el sistema."],
            ]
        );
    }

    public function privacidad(): View
    {
        $empresa = e((string) config('anlux.company_legal_name', 'La empresa titular del sistema'));
        $producto = e((string) config('app.name', 'Anlux'));
        $jurisdiccion = e((string) config('anlux.company_jurisdiction', 'México'));
        $privacyEmail = e((string) config('anlux.privacy_email', 'privacidad@ejemplo.com'));

        return $this->renderLegal(
            'Aviso de privacidad',
            'Aviso de privacidad del sistema Anlux: tratamiento de datos personales, WhatsApp y derechos ARCO.',
            [
                ['type' => 'p', 'html' => "<strong>{$empresa}</strong> (en adelante, &laquo;la Empresa&raquo;), con operaciones en <strong>{$jurisdiccion}</strong>, es responsable del tratamiento de los datos personales recabados a traves del sistema <strong>{$producto}</strong> y de los servicios relacionados, incluida la comunicacion por WhatsApp, conforme a la Ley Federal de Proteccion de Datos Personales en Posesion de los Particulares (LFPDPPP) y demas normativa aplicable."],
                ['type' => 'h2', 'text' => '1. Datos personales que recabamos'],
                ['type' => 'p', 'html' => 'Podemos recabar, entre otros, los siguientes datos:'],
                ['type' => 'ul', 'items' => [
                    '<strong>Identificacion y contacto:</strong> nombre, telefono, correo electronico.',
                    '<strong>Datos de orden de servicio:</strong> folio, equipo, descripcion del servicio, estatus, historial.',
                    '<strong>Datos de usuarios internos:</strong> nombre de tecnico, usuario de acceso, actividad en el sistema.',
                    '<strong>Comunicaciones:</strong> mensajes intercambiados por WhatsApp o correo vinculados a una orden.',
                    '<strong>Datos tecnicos:</strong> direccion IP, registros de acceso, cookies de sesion y logs de seguridad.',
                ]],
                ['type' => 'h2', 'text' => '2. Finalidades del tratamiento'],
                ['type' => 'p', 'html' => 'Utilizamos los datos personales para:'],
                ['type' => 'ul', 'items' => [
                    'Registrar, dar seguimiento y administrar ordenes de servicio.',
                    'Comunicar al cliente el estatus de su orden (recepcion, terminado, entrega, etc.).',
                    'Enviar notificaciones por correo electronico y WhatsApp cuando el cliente lo haya solicitado o autorizado.',
                    'Administrar cuentas de personal autorizado y proteger la seguridad del sistema.',
                    'Cumplir obligaciones legales y resolver controversias.',
                ]],
                ['type' => 'h2', 'text' => '3. Transferencias y encargados'],
                ['type' => 'p', 'html' => 'Podemos compartir datos con proveedores que nos ayudan a operar el sistema (hospedaje, correo, mensajeria WhatsApp/Meta), bajo obligaciones de confidencialidad y solo para las finalidades descritas.'],
                ['type' => 'h2', 'text' => '4. Derechos ARCO'],
                ['type' => 'p', 'html' => "Puedes ejercer tus derechos de Acceso, Rectificacion, Cancelacion y Oposicion enviando una solicitud a <strong>{$privacyEmail}</strong> o usando el procedimiento de <a href=\"".e(route('legal.eliminar-datos')).'">eliminacion de datos</a>.'],
                ['type' => 'h2', 'text' => '5. Conservacion'],
                ['type' => 'p', 'html' => 'Conservamos los datos el tiempo necesario para las finalidades operativas y legales aplicables.'],
                ['type' => 'h2', 'text' => '6. Contacto'],
                ['type' => 'p', 'html' => "Para dudas sobre este aviso: <strong>{$privacyEmail}</strong> ({$jurisdiccion})."],
            ]
        );
    }

    public function eliminarDatos(): View
    {
        $empresa = e((string) config('anlux.company_legal_name', 'La empresa titular del sistema'));
        $producto = e((string) config('app.name', 'Anlux'));
        $privacyEmail = e((string) config('anlux.privacy_email', 'privacidad@ejemplo.com'));
        $jurisdiccion = e((string) config('anlux.company_jurisdiction', 'México'));

        return $this->renderLegal(
            'Eliminacion de datos del usuario',
            'Como solicitar la eliminacion de tus datos personales del sistema Anlux.',
            [
                ['type' => 'p', 'html' => "<strong>{$empresa}</strong> (en adelante, &laquo;la Empresa&raquo;), responsable del sistema <strong>{$producto}</strong>, pone a tu disposicion este procedimiento para solicitar la eliminacion de tus datos personales, incluidos los datos asociados a las comunicaciones por WhatsApp."],
                ['type' => 'h2', 'text' => '1. Que datos se pueden eliminar'],
                ['type' => 'ul', 'items' => [
                    'Nombre, telefono y correo electronico de contacto.',
                    'Mensajes intercambiados por WhatsApp vinculados a una orden de servicio.',
                    'Datos de contacto asociados a tus ordenes de servicio, salvo los que la Empresa deba conservar por obligacion legal o contable.',
                ]],
                ['type' => 'h2', 'text' => '2. Como solicitar la eliminacion'],
                ['type' => 'p', 'html' => 'Para solicitar la eliminacion de tus datos puedes hacerlo por cualquiera de estos medios:'],
                ['type' => 'ul', 'items' => [
                    "<strong>Correo electronico:</strong> envia tu solicitud a <strong>{$privacyEmail}</strong> con el asunto «Eliminacion de datos».",
                    '<strong>WhatsApp:</strong> escribe al numero oficial de atencion de la Empresa indicando que deseas eliminar tus datos.',
                ]],
                ['type' => 'p', 'html' => 'Para identificar tu informacion, incluye en tu solicitud el nombre y el numero de telefono con el que te comunicaste, y si la tienes, el folio de tu orden de servicio.'],
                ['type' => 'h2', 'text' => '3. Plazo de atencion'],
                ['type' => 'p', 'html' => 'Atenderemos tu solicitud en un plazo razonable conforme a la legislacion aplicable. Una vez verificada tu identidad, eliminaremos o anonimizaremos los datos solicitados, salvo aquella informacion que debamos conservar por obligaciones legales, fiscales o para la resolucion de controversias.'],
                ['type' => 'h2', 'text' => '4. Datos procesados por WhatsApp/Meta'],
                ['type' => 'p', 'html' => 'Algunos datos de mensajeria son procesados por <strong>Meta Platforms, Inc.</strong> conforme a sus propias politicas. La eliminacion en nuestros sistemas no implica necesariamente la eliminacion en los sistemas de Meta.'],
                ['type' => 'h2', 'text' => '5. Contacto'],
                ['type' => 'p', 'html' => 'Para cualquier duda consulta nuestro <a href="'.e(route('legal.privacidad'))."\">Aviso de privacidad</a> o contacta al administrador del sistema {$producto} ({$jurisdiccion})."],
            ]
        );
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function renderLegal(string $title, string $ogDescription, array $blocks): View
    {
        return view('legal.page', [
            'pageTitle' => $title,
            'ogDescription' => $ogDescription,
            'reactPageProps' => [
                'title' => $title,
                'updatedAt' => now()->format('d/m/Y'),
                'blocks' => $blocks,
                'homeUrl' => route('home'),
                'terminosUrl' => route('legal.terminos'),
                'privacidadUrl' => route('legal.privacidad'),
                'eliminarUrl' => route('legal.eliminar-datos'),
            ],
        ]);
    }
}
