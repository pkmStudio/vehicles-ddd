<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Presentation\Console\Commands;

use App\Modules\Warehouse\Features\MoySklad\Application\Services\NomenclatureBackfillService;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use Illuminate\Console\Command;

/**
 * Artisan-команда backfill связей Warehouse-номенклатуры с товарами МойСклад.
 */
final class BackfillNomenclatureCommand extends Command
{
    protected $signature = 'warehouse:moysklad-backfill-nomenclature {--chunk=100} {--queue}';

    protected $description = 'Восстанавливает связи Warehouse-номенклатуры с товарами МойСклад';

    /**
     * Запускает backfill синхронно или ставит его в очередь по опции `--queue`.
     */
    public function handle(NomenclatureBackfillService $service, NomenclatureSyncDispatcherInterface $dispatcher): int
    {
        $chunk = max(1, (int) $this->option('chunk'));

        if ((bool) $this->option('queue')) {
            $dispatcher->dispatchBackfill($chunk);
            $this->info('Backfill МойСклад поставлен в очередь.');

            return self::SUCCESS;
        }

        $stats = $service->execute($chunk);

        $this->info(sprintf(
            'Готово. processed=%d synced=%d failed=%d skipped=%d',
            $stats['processed'],
            $stats['synced'],
            $stats['failed'],
            $stats['skipped'],
        ));

        return self::SUCCESS;
    }
}
