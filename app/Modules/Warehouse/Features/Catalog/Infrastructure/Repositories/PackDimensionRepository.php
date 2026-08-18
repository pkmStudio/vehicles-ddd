<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use Illuminate\Support\Collection;

/**
 * Читает упаковочные размеры Warehouse для Catalog-мутаций.
 */
final readonly class PackDimensionRepository implements PackDimensionRepositoryInterface
{
    /**
     * Возвращает упаковочный размер по id или null.
     *
     * Шаги:
     * 1) Собрать Eloquent query по входному признаку.
     * 2) Получить первую подходящую запись каталога.
     * 3) Преобразовать найденную модель в Data или вернуть null.
     */
    public function findById(int $id): ?PackDimensionData
    {
        $packDimension = PackDimension::query()
            ->with('type')
            ->find($id);

        return PackDimensionData::optional($packDimension);
    }

    /**
     * Возвращает упаковки по id, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, PackDimensionData>
     */
    public function findByIds(array $ids): Collection
    {
        $packDimensions = PackDimension::query()
            ->with('type')
            ->whereIn('id', $ids)
            ->get();

        return PackDimensionData::collect($packDimensions, Collection::class)->keyBy('id');
    }
}
