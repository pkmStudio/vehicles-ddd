<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\ModelData;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-набора с составом, нужный для аудита адаптеров дворников.
 */
#[MapName(SnakeCaseMapper::class)]
final class KitData extends Data
{
    /**
     * Хранит количество товаров в наборе и загруженную номенклатуру.
     *
     * @param  Collection<int, NomenclatureData>|null  $nomenclatures
     */
    public function __construct(
        public readonly int $quantityInPackage,
        public readonly ?int $id = null,
        public readonly ?Collection $nomenclatures = null,
    ) {}
}
