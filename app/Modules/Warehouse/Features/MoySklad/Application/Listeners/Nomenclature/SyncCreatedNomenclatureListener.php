<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Listeners\Nomenclature;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;

/**
 * Ставит синхронизацию МойСклад после создания Warehouse-номенклатуры.
 */
final readonly class SyncCreatedNomenclatureListener
{
    /**
     * Получает порт постановки MoySklad-задач.
     */
    public function __construct(
        private NomenclatureSyncDispatcherInterface $dispatcher,
    ) {}

    /**
     * Ставит sync job по id созданной номенклатуры, если интеграция включена.
     */
    public function handle(NomenclatureCreated $event): void
    {
        if (! (bool) config('warehouse.moysklad.nomenclature_sync.enabled', false)) {
            return;
        }

        $id = $event->nomenclature->id;
        if ($id === null) {
            return;
        }

        $this->dispatcher->dispatchSync($id);
    }
}
