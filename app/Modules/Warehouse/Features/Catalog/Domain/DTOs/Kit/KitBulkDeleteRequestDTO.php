<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit;

/**
 * DTO входящей команды на массовое удаление Warehouse-наборов.
 */
final readonly class KitBulkDeleteRequestDTO
{
    /**
     * @param  list<int>  $ids
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public array $ids,
    ) {}
}
