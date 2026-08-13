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
     *
     * Шаги:
     * 1) Проверить, был ли operation id уже принят ранее.
     * 2) Атомарно отметить новый operation id как принятый.
     * 3) Вернуть false для дубля и true для первого принятия.
     */
    public function accept(string $operationId): bool;

    /**
     * Снимает отметку принятого operationId после сбоя обработки.
     *
     * Шаги:
     * 1) Найти cache guard по operation id.
     * 2) Удалить guard, чтобы retry мог повторно выполнить mutation.
     */
    public function forgetAccepted(string $operationId): void;
}
