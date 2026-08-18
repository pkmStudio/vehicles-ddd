<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogEntityEnum;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\WarehouseCatalogMutationStatusEnum;

/**
 * Результат массового удаления наборов.
 */
final readonly class KitBulkDeleteResultDTO
{
    /**
     * @param  list<KitBulkDeleteErrorDTO>  $errors
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public WarehouseCatalogEntityEnum $entity,
        public WarehouseCatalogMutationStatusEnum $status,
        public int $requested,
        public int $deleted,
        public int $skipped,
        public int $failed,
        public array $errors = [],
    ) {}

    /**
     * Преобразует результат в payload outbound Rabbit-события.
     *
     * @return array{
     *     user_id: int,
     *     operation_id: string,
     *     entity: string,
     *     status: string,
     *     requested: int,
     *     deleted: int,
     *     skipped: int,
     *     failed: int,
     *     errors: list<array{id?: int, reason: string, business_key?: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'operation_id' => $this->operationId,
            'entity' => $this->entity->value,
            'status' => $this->status->value,
            'requested' => $this->requested,
            'deleted' => $this->deleted,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'errors' => array_map(
                static fn (KitBulkDeleteErrorDTO $error): array => $error->toArray(),
                $this->errors,
            ),
        ];
    }
}
