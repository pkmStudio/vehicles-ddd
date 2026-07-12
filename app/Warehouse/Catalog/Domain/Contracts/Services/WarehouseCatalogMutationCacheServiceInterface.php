<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Services;

/**
 * Порт cache-идемпотентности входящих мутаций Warehouse-каталога.
 */
interface WarehouseCatalogMutationCacheServiceInterface
{
    /**
     * Атомарно принимает operationId для обработки.
     */
    public function accept(string $operationId): bool;

    /**
     * Снимает отметку operationId после технического сбоя.
     */
    public function forgetAccepted(string $operationId): void;
}
