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

    protected $description = 'Ставит задачи синхронизации Warehouse-номенклатуры с товарами МойСклад';

    /**
     * Запускает backfill синхронно или ставит его в очередь по опции `--queue`.
     */
    public function handle(NomenclatureBackfillService $service, NomenclatureSyncDispatcherInterface $dispatcher): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $queueRequested = (bool) $this->option('queue');

        if ($queueRequested) {
            $dispatcher->dispatchBackfill($chunk);
            $this->info('Планировщик backfill МойСклад поставлен в очередь.');

            return self::SUCCESS;
        }

        $service->execute($chunk);

        $this->info('Задачи backfill МойСклад поставлены в очередь.');

        return self::SUCCESS;
    }
}
