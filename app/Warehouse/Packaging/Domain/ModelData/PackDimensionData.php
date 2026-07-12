<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок упаковочного размера Warehouse — читается для подбора и создаётся при отсутствии
 * подходящей коробки.
 */
#[MapName(SnakeCaseMapper::class)]
final class PackDimensionData extends Data
{
    /**
     * Хранит поля упаковочного размера.
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
    ) {}
}
