<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services;

/**
 * Описывает порт сервисной операции мутаций каталога.
 */
interface CatalogMutationCacheServiceInterface
{
    /**
     * Атомарно принимает operationId для идемпотентной обработки.
     */
    public function accept(string $operationId): bool;

    /**
     * Снимает отметку принятого operationId после сбоя обработки.
     */
    public function forgetAccepted(string $operationId): void;
}
