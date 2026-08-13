<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureIntegrationRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\NomenclatureIntegration;

/**
 * Eloquent-реализация чтения integration-state МойСклад.
 */
final readonly class NomenclatureIntegrationRepository implements NomenclatureIntegrationRepositoryInterface
{
    /**
     * Возвращает integration-state МойСклад по id номенклатуры или null.
     * Шаги:
     * 1) Построить Eloquent query по provider=moysklad.
     * 2) Ограничить query nomenclature_id.
     * 3) Вернуть optional Data-снимок первой найденной записи.
     */
    public function findByNomenclatureId(int $nomenclatureId): ?NomenclatureIntegrationData
    {
        $integration = NomenclatureIntegration::query()
            ->where('provider', NomenclatureIntegration::PROVIDER)
            ->where('nomenclature_id', $nomenclatureId)
            ->first();

        return NomenclatureIntegrationData::optional($integration);
    }

    /**
     * Возвращает integration-state для удаления по сохранённой связке или fallback external_code.
     * Шаги:
     * 1) Если integrationId передан, найти provider=moysklad запись по primary key.
     * 2) Если integrationId не передан, искать provider=moysklad запись по nomenclature_id или external_code.
     * 3) Вернуть optional Data-снимок найденной записи.
     */
    public function findForDeletion(
        int $nomenclatureId,
        string $externalCode,
        ?int $integrationId = null,
    ): ?NomenclatureIntegrationData {
        if ($integrationId !== null) {
            $integration = NomenclatureIntegration::query()
                ->where('provider', NomenclatureIntegration::PROVIDER)
                ->find($integrationId);

            return NomenclatureIntegrationData::optional($integration);
        }

        $integration = NomenclatureIntegration::query()
            ->where('provider', NomenclatureIntegration::PROVIDER)
            ->where(function ($query) use ($nomenclatureId, $externalCode): void {
                $query
                    ->where('nomenclature_id', $nomenclatureId)
                    ->orWhere('external_code', $externalCode);
            })
            ->first();

        return NomenclatureIntegrationData::optional($integration);
    }
}
