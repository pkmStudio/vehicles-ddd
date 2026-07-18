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
     * Отмечает успешную синхронизацию связи.
     */
    public function markSynced(
        NomenclatureIntegrationData $integration,
        ?string $externalId,
        ?string $externalCode,
        string $payloadHash,
    ): void;

    /**
     * Отмечает ошибку синхронизации связи.
     */
    public function markFailed(NomenclatureIntegrationData $integration, string $error): void;

    /**
     * Отмечает связь удалённой в МойСклад.
     */
    public function markDeleted(?NomenclatureIntegrationData $integration, ?string $externalId = null): void;

    /**
     * Создаёт/обновляет failed-связь для backfill.
     */
    public function markBackfillFailed(int $nomenclatureId, string $externalCode, string $error): void;
}
