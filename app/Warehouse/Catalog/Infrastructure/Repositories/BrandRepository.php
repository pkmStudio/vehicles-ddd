<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Infrastructure\Repositories;

use App\Warehouse\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Catalog\Domain\DTOs\Brand\BrandDeletionBlockersDTO;
use App\Warehouse\Catalog\Domain\ModelData\BrandData;
use App\Warehouse\Catalog\Infrastructure\Models\Brand;
use App\Warehouse\Catalog\Infrastructure\Models\Nomenclature;

/**
 * Читает Warehouse-бренды для Catalog-мутаций.
 */
final readonly class BrandRepository implements BrandRepositoryInterface
{
    /**
     * Возвращает бренд по id или null.
     */
    public function find(int $id): ?BrandData
    {
        return BrandData::optional(Brand::query()->find($id));
    }

    /**
     * Возвращает первый бренд с таким именем без учёта регистра.
     */
    public function firstByName(string $name): ?BrandData
    {
        $brand = Brand::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->first();

        return BrandData::optional($brand);
    }

    /**
     * Проверяет, занято ли имя другим брендом без учёта регистра.
     */
    public function nameExistsForAnother(string $name, int $id): bool
    {
        return Brand::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->where('id', '!=', $id)
            ->exists();
    }

    /**
     * Собирает зависимости, блокирующие удаление бренда.
     */
    public function deletionBlockers(int $id): ?BrandDeletionBlockersDTO
    {
        if (! Brand::query()->whereKey($id)->exists()) {
            return null;
        }

        return new BrandDeletionBlockersDTO(
            nomenclaturesCount: Nomenclature::query()->where('brand_id', $id)->count(),
        );
    }
}
