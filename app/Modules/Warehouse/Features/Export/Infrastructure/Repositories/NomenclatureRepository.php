<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\Nomenclature;
use Illuminate\Support\Collection;

/**
 * Читает Warehouse-номенклатуру выбранного типа для экспорта.
 */
final readonly class NomenclatureRepository implements NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру одного типа с брендом и типом для строк Excel.
     *
     * Шаги:
     * 1) Построить Eloquent query с загрузкой type и brand.
     * 2) Отфильтровать записи по type_id и отсортировать по id.
     * 3) Преобразовать Eloquent collection в NomenclatureData collection.
     *
     * @return Collection<int, NomenclatureData>
     */
    public function forType(int $typeId): Collection
    {
        $items = Nomenclature::query()
            ->with(['type', 'brand'])
            ->where('type_id', $typeId)
            ->orderBy('id')
            ->get();

        return NomenclatureData::collect($items, Collection::class);
    }
}
