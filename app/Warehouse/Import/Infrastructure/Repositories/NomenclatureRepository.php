<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Repositories;

use App\Warehouse\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Import\Domain\ModelData\Kit\NomenclatureForKitData;
use App\Warehouse\Import\Infrastructure\Models\Nomenclature;
use Illuminate\Support\Collection;

/**
 * Читает уже сохранённую Warehouse-номенклатуру по артикулам — для сборки состава Kit.
 */
final readonly class NomenclatureRepository implements NomenclatureRepositoryInterface
{
    /**
     * Возвращает найденные номенклатуры (с загруженным типом), индексированные по part_number.
     *
     * @param  array<int, string>  $partNumbers
     * @return Collection<string, NomenclatureForKitData>
     */
    public function findByPartNumbers(array $partNumbers): Collection
    {
        $items = Nomenclature::query()
            ->with('type')
            ->whereIn('part_number', $partNumbers)
            ->get();

        return NomenclatureForKitData::collect($items, Collection::class)->keyBy('partNumber');
    }
}
