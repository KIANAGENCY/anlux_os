<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SersopCatalogService;
use Illuminate\Console\Command;

class ExactoSyncSersopPreciosSinIvaCommand extends Command
{
    protected $signature = 'exacto:sync-sersop-precios-sin-iva';

    protected $description = 'Reemplaza sersop_catalog con PRECIOS SIN IVA de config/servicios_sersop.php';

    public function handle(SersopCatalogService $catalog): int
    {
        try {
            $rows = $catalog->syncFromConfig();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Catálogo SERSOP actualizado: '.count($rows).' servicios (precios sin IVA).');
        foreach ($rows as $row) {
            $this->line(sprintf('  %s = %.2f', $row['clave'], $row['precio']));
        }

        return self::SUCCESS;
    }
}
