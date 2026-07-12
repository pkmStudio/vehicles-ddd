<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs;

use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationOperationEnum;
use App\Warehouse\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;

/**
 * Payload результата точечной мутации Warehouse-каталога для исходящего RabbitMQ-события.
 */
final readonly class WarehouseCatalogMutationResultDTO
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public WarehouseCatalogEntityEnum $entity,
        public WarehouseCatalogMutationOperationEnum $operation,
        public WarehouseCatalogMutationStatusEnum $status,
        public ?int $recordId = null,
        public ?string $businessKey = null,
        public ?string $reason = null,
        public array $errors = [],
    ) {}

    /**
     * Преобразует результат мутации в snake_case payload для RabbitMQ.
     *
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
            'record_id' => $this->recordId,
            'business_key' => $this->businessKey,
            'reason' => $this->reason,
            'errors' => $this->errors,
        ];
    }
}
