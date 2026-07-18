<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension;

/**
 * DTO входящей команды на удаление упаковочного размера Warehouse.
 */
final readonly class DeletePackDimensionRequestDTO
{
    /**
     * Хранит id удаляемой упаковки и контекст операции.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
    ) {}
}
