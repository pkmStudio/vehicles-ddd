<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок бренда Warehouse, который Repository отдаёт для экспортируемой номенклатуры.
 */
#[MapName(SnakeCaseMapper::class)]
final class BrandData extends Data
{
    /**
     * Хранит поля бренда, читаемые из Warehouse-схемы.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $numberSert,
        public readonly string $dateStart,
        public readonly string $dateEnd,
        public readonly string $char,
        public readonly ?int $id = null,
    ) {}
}
