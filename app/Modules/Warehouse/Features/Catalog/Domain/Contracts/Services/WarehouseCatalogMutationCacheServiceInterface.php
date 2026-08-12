<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services;

/**
 * Порт cache-идемпотентности входящих мутаций Warehouse-каталога.
 */
interface WarehouseCatalogMutationCacheServiceInterface
{
    /**
     * Атомарно принимает operationId для обработки.
     *
     * Шаги:
     * 1) Сформировать cache key по operationId.
     * 2) Попробовать атомарно записать флаг принятой операции.
     * 3) Вернуть false для повторного сообщения с тем же operationId.
     */
    public function accept(string $operationId): bool;

    /**
     * Снимает отметку operationId после технического сбоя.
     *
     * Шаги:
     * 1) Сформировать cache key по operationId.
     * 2) Удалить флаг принятой операции из cache.
     * 3) Позволить повторную обработку после технического сбоя.
     */
    public function forgetAccepted(string $operationId): void;
}
