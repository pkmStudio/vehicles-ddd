<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureIntegrationData;

/**
 * Порт записи integration-state МойСклад.
 */
interface NomenclatureIntegrationCommandInterface
{
    /**
     * Создаёт pending-связь для номенклатуры.
     * Шаги:
     * 1) Принять id Warehouse-номенклатуры.
     * 2) Создать integration-state provider=moysklad в статусе pending.
     * 3) Вернуть Data-снимок созданной связи.
     */
    public function createPendingForNomenclature(int $nomenclatureId): NomenclatureIntegrationData;

    /**
     * Отмечает успешную синхронизацию связи.
     * Шаги:
     * 1) Найти запись integration-state по id переданного Data-снимка.
     * 2) Обновить external ids, статус synced, synced_at и payload_hash.
     * 3) Очистить last_error.
     */
    public function markSynced(
        NomenclatureIntegrationData $integration,
        ?string $externalId,
        ?string $externalCode,
        string $payloadHash,
    ): void;

    /**
     * Отмечает ошибку синхронизации связи.
     * Шаги:
     * 1) Найти запись integration-state по id переданного Data-снимка.
     * 2) Перевести статус в failed.
     * 3) Сохранить текст последней ошибки.
     */
    public function markFailed(NomenclatureIntegrationData $integration, string $error): void;

    /**
     * Отмечает связь удалённой в МойСклад.
     * Шаги:
     * 1) Если integration отсутствует, завершить command без записи.
     * 2) Найти запись integration-state по id.
     * 3) Обновить external_id, статус deleted, synced_at и очистить error/hash.
     */
    public function markDeleted(?NomenclatureIntegrationData $integration, ?string $externalId = null): void;
}
