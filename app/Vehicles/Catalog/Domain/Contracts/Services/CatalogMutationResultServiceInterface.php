<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Services;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationRejectReasonEnum;

interface CatalogMutationResultServiceInterface
{
    public function completed(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
        ?int $recordId = null,
    ): CatalogMutationResultDTO;

    /**
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

    public function failed(
        int $userId,
        string $operationId,
        CatalogEntityEnum $entity,
        CatalogMutationOperationEnum $operation,
        int $externalId,
    ): CatalogMutationResultDTO;
}
