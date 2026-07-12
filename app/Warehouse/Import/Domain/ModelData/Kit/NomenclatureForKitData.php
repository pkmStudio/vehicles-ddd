<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\ModelData\Kit;

use App\Warehouse\Import\Domain\ModelData\TypeData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Read-only снимок уже сохранённой Warehouse-номенклатуры для сборки Kit при импорте набора.
 * Отдельный от write-шного `Domain\ModelData\NomenclatureData` класс: несёт `type` (нужен
 * KitProperties для резолва стратегии состава), которого нет и не должно быть в write-снимке —
 * `NomenclatureCommand` шлёт `toArray()` в БД напрямую, лишнее поле там сломает запись.
 */
#[MapName(SnakeCaseMapper::class)]
final class NomenclatureForKitData extends Data
{
    /**
     * @param  array<int, string>  $material
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $id,
        public readonly int $typeId,
        public readonly string $partNumber,
        public readonly int $quantityInPak,
        public readonly int $quantityPak,
        public readonly int $weight,
        public readonly array $material,
        public readonly array $details,
        public readonly ?TypeData $type = null,
    ) {}
}
