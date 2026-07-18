<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Commands;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Commands\NomenclatureIntegrationCommandInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Enums\MoySkladIntegrationStatusEnum;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\NomenclatureIntegration;

/**
 * Eloquent-реализация записи integration-state МойСклад.
 */
final readonly class NomenclatureIntegrationCommand implements NomenclatureIntegrationCommandInterface
{
    /**
     * Отмечает successful sync и сохраняет external ids/hash.
     */
    public function markSynced(
        NomenclatureIntegrationData $integration,
        ?string $externalId,
        ?string $externalCode,
        string $payloadHash,
    ): void {
        NomenclatureIntegration::query()
            ->whereKey($integration->id)
            ->update([
                'external_id' => $externalId ?? $integration->externalId,
                'external_code' => $externalCode ?? $integration->externalCode,
                'sync_status' => MoySkladIntegrationStatusEnum::Synced->value,
                'synced_at' => now(),
                'last_error' => null,
                'payload_hash' => $payloadHash,
                'updated_at' => now(),
            ]);
    }

    /**
     * Отмечает failed sync и сохраняет текст ошибки.
     */
    public function markFailed(NomenclatureIntegrationData $integration, string $error): void
    {
        NomenclatureIntegration::query()
            ->whereKey($integration->id)
            ->update([
                'sync_status' => MoySkladIntegrationStatusEnum::Failed->value,
                'last_error' => $error,
                'updated_at' => now(),
            ]);
    }

    /**
     * Отмечает удаление товара МойСклад, если есть integration-связь.
     */
    public function markDeleted(?NomenclatureIntegrationData $integration, ?string $externalId = null): void
    {
        if ($integration === null) {
            return;
        }

        NomenclatureIntegration::query()
            ->whereKey($integration->id)
            ->update([
                'external_id' => $externalId ?? $integration->externalId,
                'sync_status' => MoySkladIntegrationStatusEnum::Deleted->value,
                'synced_at' => now(),
                'last_error' => null,
                'payload_hash' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Создаёт/обновляет failed-связь при ошибке backfill.
     */
    public function markBackfillFailed(int $nomenclatureId, string $externalCode, string $error): void
    {
        NomenclatureIntegration::query()->updateOrCreate(
            attributes: [
                'provider' => NomenclatureIntegration::PROVIDER,
                'nomenclature_id' => $nomenclatureId,
            ],
            values: [
                'external_code' => $externalCode,
                'sync_status' => MoySkladIntegrationStatusEnum::Failed->value,
                'last_error' => $error,
            ],
        );
    }
}
