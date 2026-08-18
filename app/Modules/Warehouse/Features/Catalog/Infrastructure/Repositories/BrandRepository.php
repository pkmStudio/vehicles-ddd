<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use Illuminate\Support\Collection;

/**
 * Читает Warehouse-бренды для Catalog-мутаций.
 */
final readonly class BrandRepository implements BrandRepositoryInterface
{
    /**
     * Возвращает бренд по внутреннему идентификатору или null.
     *
     * Шаги:
     * 1) Собрать Eloquent query по входному признаку.
     * 2) Получить первую подходящую запись каталога.
     * 3) Преобразовать найденную модель в Data или вернуть null.
     */
    public function findById(int $id): ?BrandData
    {
        return BrandData::optional(Brand::query()->find($id));
    }

    /**
     * Возвращает бренд по имени или null.
     *
     * Шаги:
     * 1) Собрать Eloquent query по входному признаку.
     * 2) Получить первую подходящую запись каталога.
     * 3) Преобразовать найденную модель в Data или вернуть null.
     */
    public function findByName(string $name): ?BrandData
    {
        $brand = Brand::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->first();

        return BrandData::optional($brand);
    }

    /**
     * Возвращает бренды по id, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, BrandData>
     */
    public function findByIds(array $ids): Collection
    {
        $brands = Brand::query()
            ->whereIn('id', $ids)
            ->get();

        return BrandData::collect($brands, Collection::class)->keyBy('id');
    }
}
