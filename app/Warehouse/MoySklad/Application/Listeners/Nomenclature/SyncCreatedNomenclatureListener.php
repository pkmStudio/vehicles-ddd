<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Application\Listeners\Nomenclature;

use App\Warehouse\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;

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

        $id = $event->nomenclature['id'] ?? null;
        if (! is_numeric($id)) {
            return;
        }

        $this->dispatcher->dispatchSync((int) $id);
    }
}
