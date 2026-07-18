<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Infrastructure\Repositories;

use App\Warehouse\MoySklad\Domain\Contracts\Repositories\NomenclatureIntegrationRepositoryInterface;
use App\Warehouse\MoySklad\Domain\Enums\MoySkladIntegrationStatusEnum;
use App\Warehouse\MoySklad\Domain\ModelData\NomenclatureIntegrationData;
use App\Warehouse\MoySklad\Infrastructure\Models\NomenclatureIntegration;

/**
 * Eloquent-реализация чтения integration-state МойСклад.
 */
final readonly class NomenclatureIntegrationRepository implements NomenclatureIntegrationRepositoryInterface
{
    /**
     * Возвращает существующую связь или создаёт pending-связь для номенклатуры.
     */
    public function firstOrCreateForNomenclature(int $nomenclatureId): NomenclatureIntegrationData
    {
        $integration = NomenclatureIntegration::query()->firstOrCreate(
            attributes: [
                'provider' => NomenclatureIntegration::PROVIDER,
                'nomenclature_id' => $nomenclatureId,
            ],
            values: [
                'sync_status' => MoySkladIntegrationStatusEnum::Pending->value,
            ],
        );

        return NomenclatureIntegrationData::from($integration);
    }

    /**
     * Возвращает связь номенклатуры с МойСклад или null.
     */
    public function firstForNomenclature(int $nomenclatureId): ?NomenclatureIntegrationData
    {
        $integration = NomenclatureIntegration::query()
            ->where('provider', NomenclatureIntegration::PROVIDER)
            ->where('nomenclature_id', $nomenclatureId)
            ->first();

        return NomenclatureIntegrationData::optional($integration);
    }

    /**
     * Находит связь для delete workflow по явному id, локальному id или externalCode.
     */
    public function findForDelete(int $nomenclatureId, string $externalCode, ?int $integrationId = null): ?NomenclatureIntegrationData
    {
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
