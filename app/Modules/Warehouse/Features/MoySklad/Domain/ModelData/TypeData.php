<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-типа для группировки товаров МойСклад.
 */
#[MapName(SnakeCaseMapper::class)]
final class TypeData extends Data
{
    /**
     * Хранит минимальные поля типа, нужные MoySklad-фиче.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $char = null,
    ) {}
}
