<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Nomenclature;
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
     * @return Collection<string, NomenclatureData>
     */
    public function findByPartNumbers(array $partNumbers): Collection
    {
        $items = Nomenclature::query()
            ->with('type')
            ->whereIn('part_number', $partNumbers)
            ->get();

        return NomenclatureData::collect($items, Collection::class)->keyBy('partNumber');
    }
}
