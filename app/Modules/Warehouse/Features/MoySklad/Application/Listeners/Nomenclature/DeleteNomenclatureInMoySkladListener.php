<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Listeners\Nomenclature;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Modules\Warehouse\Shared\Domain\DTOs\Events\NomenclatureIntegrationDeletionContextDTO;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted;
use Illuminate\Support\Collection;

/**
 * Ставит удаление товара МойСклад после удаления локальной Warehouse-номенклатуры.
 */
final readonly class DeleteNomenclatureInMoySkladListener
{
    /**
     * Получает порт постановки MoySklad-задач.
     */
    public function __construct(
        private NomenclatureSyncDispatcherInterface $dispatcher,
    ) {}

    /**
     * Ставит delete job с сохранёнными внешними идентификаторами удалённой номенклатуры.
     */
    public function handle(NomenclatureDeleted $event): void
    {
        if (! (bool) config('warehouse.moysklad.nomenclature_sync.enabled', false)) {
            return;
        }

        $moySkladIntegration = $this->resolveMoySkladIntegration($event->integrations);

        $this->dispatcher->dispatchDelete(
            nomenclatureId: $event->nomenclatureId,
            partNumber: $event->partNumber,
            externalId: $moySkladIntegration?->externalId,
            integrationId: $moySkladIntegration?->id,
        );
    }

    /**
     * Выбирает MoySklad integration context из generic shared event payload.
     *
     * @param  Collection<int, NomenclatureIntegrationDeletionContextDTO>  $integrations
     */
    private function resolveMoySkladIntegration(Collection $integrations): ?NomenclatureIntegrationDeletionContextDTO
    {
        foreach ($integrations as $integration) {
            if ($integration->provider === 'moysklad') {
                return $integration;
            }
        }

        return null;
    }
}
