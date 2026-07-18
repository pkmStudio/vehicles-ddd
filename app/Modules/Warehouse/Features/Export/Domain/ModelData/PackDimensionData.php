<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок упаковочного размера Warehouse, связанного с экспортируемым набором.
 */
#[MapName(SnakeCaseMapper::class)]
final class PackDimensionData extends Data
{
    /**
     * Хранит поля упаковочного размера и опционально загруженный тип.
     */
    public function __construct(
        public readonly string $name,
        public readonly int $weight,
        public readonly int $width,
        public readonly int $height,
        public readonly int $length,
        public readonly int $price,
        public readonly int $typeId,
        public readonly bool $generated = false,
        public readonly ?int $id = null,
        public readonly ?TypeData $type = null,
    ) {}
}
