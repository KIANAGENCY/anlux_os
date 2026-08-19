<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class LegalController extends Controller
{
    public function terminos(): View
    {
        return view('legal.terminos', [
            'pageTitle' => 'Terminos y condiciones',
            'ogDescription' => 'Terminos y condiciones de uso del sistema Exacto para gestion de ordenes de servicio.',
        ]);
    }

    public function privacidad(): View
    {
        return view('legal.privacidad', [
            'pageTitle' => 'Aviso de privacidad',
            'ogDescription' => 'Aviso de privacidad del sistema Exacto: tratamiento de datos personales, WhatsApp y derechos ARCO.',
        ]);
    }

    public function eliminarDatos(): View
    {
        return view('legal.eliminar-datos', [
            'pageTitle' => 'Eliminacion de datos',
            'ogDescription' => 'Como solicitar la eliminacion de tus datos personales del sistema Exacto.',
        ]);
    }
}
