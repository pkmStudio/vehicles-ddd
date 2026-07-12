<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок типа Warehouse-номенклатуры для резолва detail-шаблона и type_id при импорте.
 */
#[MapName(SnakeCaseMapper::class)]
final class TypeData extends Data
{
    /**
     * Хранит имя, код char и id типа номенклатуры.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $char = null,
        public readonly ?int $id = null,
    ) {}
}
