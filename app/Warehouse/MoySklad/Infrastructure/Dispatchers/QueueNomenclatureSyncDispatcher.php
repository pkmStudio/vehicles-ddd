<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Infrastructure\Dispatchers;

use App\Warehouse\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Warehouse\MoySklad\Infrastructure\Jobs\BackfillNomenclatureIntegrationJob;
use App\Warehouse\MoySklad\Infrastructure\Jobs\DeleteNomenclatureJob;
use App\Warehouse\MoySklad\Infrastructure\Jobs\SyncNomenclatureJob;

/**
 * Laravel Queue-адаптер постановки задач синхронизации МойСклад.
 */
final readonly class QueueNomenclatureSyncDispatcher implements NomenclatureSyncDispatcherInterface
{
    /**
     * Ставит job синхронизации одной номенклатуры.
     */
    public function dispatchSync(int $nomenclatureId): void
    {
        SyncNomenclatureJob::dispatch($nomenclatureId);
    }

    /**
     * Ставит job удаления товара МойСклад.
     */
    public function dispatchDelete(int $nomenclatureId, string $partNumber, ?string $externalId = null, ?int $integrationId = null): void
    {
        DeleteNomenclatureJob::dispatch(
            nomenclatureId: $nomenclatureId,
            partNumber: $partNumber,
            externalId: $externalId,
            integrationId: $integrationId,
        );
    }

    /**
     * Ставит job массового backfill.
     */
    public function dispatchBackfill(int $chunk = 100): void
    {
        BackfillNomenclatureIntegrationJob::dispatch($chunk);
    }
}
