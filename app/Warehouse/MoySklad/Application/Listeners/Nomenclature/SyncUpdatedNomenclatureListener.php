<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Application\Listeners\Nomenclature;

use App\Warehouse\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;

/**
 * Ставит синхронизацию МойСклад после обновления Warehouse-номенклатуры.
 */
final readonly class SyncUpdatedNomenclatureListener
{
    /**
     * Получает порт постановки MoySklad-задач.
     */
    public function __construct(
        private NomenclatureSyncDispatcherInterface $dispatcher,
    ) {}

    /**
     * Ставит sync job по id обновлённой номенклатуры, если интеграция включена.
     */
    public function handle(NomenclatureUpdated $event): void
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
