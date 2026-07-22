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
     * Возвращает номенклатуру по id или null.
     */
    public function findById(int $id): ?NomenclatureData
    {
        $nomenclature = Nomenclature::query()
            ->with('type')
            ->find($id);

        return NomenclatureData::optional($nomenclature);
    }

    /**
     * Возвращает номенклатуру по артикулу или null.
     */
    public function findByPartNumber(string $partNumber): ?NomenclatureData
    {
        $nomenclature = Nomenclature::query()
            ->with('type')
            ->where('part_number', $partNumber)
            ->first();

        return NomenclatureData::optional($nomenclature);
    }

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
