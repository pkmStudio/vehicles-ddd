<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;

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
}
