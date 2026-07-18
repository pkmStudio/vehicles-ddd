<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension;

/**
 * DTO входящей команды на создание упаковочного размера Warehouse.
 */
final readonly class CreatePackDimensionRequestDTO
{
    /**
     * Хранит валидированные поля создания упаковочного размера.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
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
