<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;

/**
 * Описывает порт сервисной операции мутаций каталога.
 */
interface CatalogMutationResultServiceInterface
{
    /**
     * Собирает результат мутации со статусом completed.
     *
     * Шаги:
     * 1) Создать DTO результата мутации.
     * 2) Передать DTO в notification-порт.
     * 3) Вернуть опубликованный DTO вызывающему коду.
     */
    public function completed(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
        ?int $recordId = null,
    ): CatalogMutationResultDTO;

    /**
     * Собирает результат мутации со статусом rejected.
     *
     * Шаги:
     * 1) Создать DTO результата с reason, optional errors и optional record id.
     * 2) Передать DTO в notification-порт.
     * 3) Вернуть опубликованный DTO вызывающему коду.
     *
     * @param  array<string, mixed>  $errors
     */
    public function rejected(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
        CatalogMutationRejectReasonEnum $reason,
        array $errors = [],
        ?int $recordId = null,
    ): CatalogMutationResultDTO;

    /**
     * Собирает результат мутации со статусом failed.
     *
     * Шаги:
     * 1) Создать DTO результата мутации.
     * 2) Передать DTO в notification-порт.
     * 3) Вернуть опубликованный DTO вызывающему коду.
     */
    public function failed(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
    ): CatalogMutationResultDTO;
}
