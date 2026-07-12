<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\PackDimension;

/**
 * DTO входящей команды на обновление упаковочного размера Warehouse.
 */
final readonly class UpdatePackDimensionRequestDTO
{
    /**
     * Хранит валидированные поля обновления упаковочного размера.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
        public string $name,
        public int $weight,
        public int $width,
        public int $height,
        public int $length,
        public int $price,
        public int $typeId,
        public bool $generated = false,
    ) {}
}
