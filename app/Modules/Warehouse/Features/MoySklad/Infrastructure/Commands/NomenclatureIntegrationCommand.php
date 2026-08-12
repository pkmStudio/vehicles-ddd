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
     * Создаёт pending-связь для номенклатуры.
     * Шаги:
     * 1) Создать NomenclatureIntegration с provider=moysklad.
     * 2) Записать nomenclature_id и статус pending.
     * 3) Вернуть Data-снимок созданной Eloquent-модели.
     */
    public function createPendingForNomenclature(int $nomenclatureId): NomenclatureIntegrationData
    {
        $integration = NomenclatureIntegration::query()->create([
            'provider' => NomenclatureIntegration::PROVIDER,
            'nomenclature_id' => $nomenclatureId,
            'sync_status' => MoySkladIntegrationStatusEnum::Pending->value,
        ]);

        return NomenclatureIntegrationData::from($integration);
    }

    /**
     * Отмечает successful sync и сохраняет external ids/hash.
     * Шаги:
     * 1) Найти integration row по primary key Data-снимка.
     * 2) Сохранить новые external_id/external_code или оставить прежние fallback значения.
     * 3) Установить статус synced, synced_at, payload_hash и updated_at.
     * 4) Очистить last_error.
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
     * Шаги:
     * 1) Найти integration row по primary key Data-снимка.
     * 2) Установить статус failed.
     * 3) Сохранить last_error и updated_at.
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
     * Шаги:
     * 1) Если integration Data отсутствует, выйти без записи.
     * 2) Найти integration row по primary key Data-снимка.
     * 3) Сохранить external_id из аргумента или прежнего state.
     * 4) Установить статус deleted, synced_at и updated_at.
     * 5) Очистить last_error и payload_hash.
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
}
