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
