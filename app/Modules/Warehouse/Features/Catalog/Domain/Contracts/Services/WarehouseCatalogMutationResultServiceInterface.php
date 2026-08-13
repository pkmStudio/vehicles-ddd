<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationRejectReasonEnum;

/**
 * Порт сборки и публикации унифицированных результатов мутаций Warehouse-каталога.
 */
interface WarehouseCatalogMutationResultServiceInterface
{
    /**
     * Собирает и публикует completed-результат.
     *
     * Шаги:
     * 1) Принять контекст успешной мутации и бизнес-ключ.
     * 2) Собрать DTO результата со статусом completed.
     * 3) Передать результат в notification-сервис и вернуть DTO.
     */
    public function completed(
        int $userId,
        string $operationId,
        WarehouseCatalogEntityEnum $entity,
        WarehouseCatalogMutationOperationEnum $operation,
        ?int $recordId = null,
        ?string $businessKey = null,
    ): WarehouseCatalogMutationResultDTO;

    /**
     * Собирает и публикует rejected-результат.
     *
     * @param  array<string, mixed>  $errors
     *
     * Шаги:
     * 1) Принять контекст отклонения и причину.
     * 2) Собрать DTO результата со статусом rejected.
     * 3) Передать результат в notification-сервис и вернуть DTO.
     */
    public function rejected(
        int $userId,
        string $operationId,
        WarehouseCatalogEntityEnum $entity,
        WarehouseCatalogMutationOperationEnum $operation,
        WarehouseCatalogMutationRejectReasonEnum $reason,
        array $errors = [],
        ?int $recordId = null,
        ?string $businessKey = null,
    ): WarehouseCatalogMutationResultDTO;

    /**
     * Собирает и публикует failed-результат.
     *
     * Шаги:
     * 1) Принять контекст технического сбоя мутации.
     * 2) Собрать DTO результата со статусом failed.
     * 3) Отправить уведомление о неуспешном завершении.
     */
    public function failed(
        int $userId,
        string $operationId,
        WarehouseCatalogEntityEnum $entity,
        WarehouseCatalogMutationOperationEnum $operation,
        ?int $recordId = null,
        ?string $businessKey = null,
    ): WarehouseCatalogMutationResultDTO;
}
