<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-бренда для описания товара МойСклад.
 */
#[MapName(SnakeCaseMapper::class)]
final class BrandData extends Data
{
    /**
     * Хранит минимальные поля бренда, нужные MoySklad-фиче.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
