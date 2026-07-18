<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Domain\Contracts\Services;

/**
 * Порт синхронизации Warehouse-номенклатуры с товарами МойСклад.
 */
interface NomenclatureSyncServiceInterface
{
    /**
     * Синхронизирует одну локальную номенклатуру с товаром МойСклад.
     */
    public function sync(int $nomenclatureId): void;

    /**
     * Удаляет товар МойСклад для локально удалённой номенклатуры.
     */
    public function delete(int $nomenclatureId, string $partNumber, ?string $externalId = null, ?int $integrationId = null): void;
}
