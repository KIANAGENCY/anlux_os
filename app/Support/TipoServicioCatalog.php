<?php



declare(strict_types=1);



namespace App\Support;



final class TipoServicioCatalog

{

    /** @return list<string> */

    public static function values(): array

    {

        return [

            '1. Mantenimiento',

            '2. Reparacion',

            '3. Instalacion',

            '4. Garantia',

            '5. Revision',

        ];

    }

}


