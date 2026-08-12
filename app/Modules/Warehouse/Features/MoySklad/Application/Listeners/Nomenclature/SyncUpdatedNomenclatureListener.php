<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Listeners\Nomenclature;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;

/**
 * Ставит синхронизацию МойСклад после обновления Warehouse-номенклатуры.
 */
final readonly class SyncUpdatedNomenclatureListener
{
    /**
     * Получает порт постановки MoySklad-задач.
     * Шаги:
     * 1) Сохранить dispatcher, который скрывает конкретные queue jobs от listener-а.
     */
    public function __construct(
        private NomenclatureSyncDispatcherInterface $dispatcher,
    ) {}

    /**
     * Ставит sync job по id обновлённой номенклатуры, если интеграция включена.
     * Шаги:
     * 1) Проверить feature flag синхронизации MoySklad.
     * 2) Взять id обновлённой номенклатуры из shared event payload.
     * 3) Если id отсутствует, завершить обработку без постановки job.
     * 4) Передать id в dispatcher sync job.
     */
    public function handle(NomenclatureUpdated $event): void
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
