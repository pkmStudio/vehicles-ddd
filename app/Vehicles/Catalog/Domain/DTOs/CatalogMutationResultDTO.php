<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs;

use App\Vehicles\Catalog\Domain\Enums\CatalogEntityEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationStatusEnum;

/**
 * Передает параметры сценария или результат мутации каталога.
 */
final readonly class CatalogMutationResultDTO
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public CatalogEntityEnum $entity,
        public CatalogMutationOperationEnum $operation,
        public CatalogMutationStatusEnum $status,
        public int $externalId,
        public ?int $recordId = null,
        public ?string $reason = null,
        public array $errors = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'operation_id' => $this->operationId,
            'entity' => $this->entity->value,
            'operation' => $this->operation->value,
            'status' => $this->status->value,
            'external_id' => $this->externalId,
            'record_id' => $this->recordId,
            'reason' => $this->reason,
            'errors' => $this->errors,
        ];
    }
}
