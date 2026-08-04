<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureIntegrationRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\DTOs\NomenclatureIntegrationLookupDTO;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\NomenclatureIntegration;

/**
 * Eloquent-реализация чтения integration-state МойСклад.
 */
final readonly class NomenclatureIntegrationRepository implements NomenclatureIntegrationRepositoryInterface
{
    /**
     * Возвращает integration-state МойСклад по typed lookup-критерию или null.
     */
    public function find(NomenclatureIntegrationLookupDTO $lookup): ?NomenclatureIntegrationData
    {
        if ($lookup->integrationId !== null) {
            $integration = NomenclatureIntegration::query()
                ->where('provider', NomenclatureIntegration::PROVIDER)
                ->find($lookup->integrationId);

            return NomenclatureIntegrationData::optional($integration);
        }

        $query = NomenclatureIntegration::query()
            ->where('provider', NomenclatureIntegration::PROVIDER)
            ->where('nomenclature_id', $lookup->nomenclatureId);

        if ($lookup->externalCode === null) {
            return NomenclatureIntegrationData::optional($query->first());
        }

        $integration = NomenclatureIntegration::query()
            ->where('provider', NomenclatureIntegration::PROVIDER)
            ->where(function ($query) use ($lookup): void {
                $query
                    ->where('nomenclature_id', $lookup->nomenclatureId)
                    ->orWhere('external_code', $lookup->externalCode);
            })
            ->first();

        return NomenclatureIntegrationData::optional($integration);
    }
}
