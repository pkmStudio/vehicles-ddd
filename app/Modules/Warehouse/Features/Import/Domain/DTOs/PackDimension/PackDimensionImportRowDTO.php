<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\DTOs\PackDimension;

/**
 * Валидированная строка импорта Warehouse-упаковки.
 */
final readonly class PackDimensionImportRowDTO
{
    public function __construct(
        public ?int $id,
        public string $name,
        public int $weight,
        public int $width,
        public int $height,
        public int $length,
        public int $price,
        public string $type,
    ) {}
}
