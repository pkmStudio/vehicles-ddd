<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Dispatchers;

/**
 * Порт постановки MoySklad sync/delete/backfill задач в асинхронное исполнение.
 */
interface NomenclatureSyncDispatcherInterface
{
    /**
     * Ставит синхронизацию одной номенклатуры.
     */
    public function dispatchSync(int $nomenclatureId): void;

    /**
     * Ставит удаление товара МойСклад.
     */
    public function dispatchDelete(int $nomenclatureId, string $partNumber, ?string $externalId = null, ?int $integrationId = null): void;

    /**
     * Ставит массовый backfill связей.
     */
    public function dispatchBackfill(int $chunk = 100): void;
}
