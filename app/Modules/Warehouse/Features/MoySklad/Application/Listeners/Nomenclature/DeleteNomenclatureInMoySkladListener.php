<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Application\Listeners\Nomenclature;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted;

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
            externalId: isset($moySkladIntegration['external_id']) && is_string($moySkladIntegration['external_id'])
                ? $moySkladIntegration['external_id']
                : null,
            integrationId: isset($moySkladIntegration['id']) ? (int) $moySkladIntegration['id'] : null,
        );
    }

    /**
     * Выбирает MoySklad integration context из generic shared event payload.
     *
     * @param  array<int, array<string, mixed>>  $integrations
     * @return array<string, mixed>
     */
    private function resolveMoySkladIntegration(array $integrations): array
    {
        foreach ($integrations as $integration) {
            if (($integration['provider'] ?? null) === 'moysklad') {
                return $integration;
            }
        }

        return [];
    }
}
