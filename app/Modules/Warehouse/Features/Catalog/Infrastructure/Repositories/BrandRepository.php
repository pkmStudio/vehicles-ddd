<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandDeletionBlockersDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;

/**
 * Читает Warehouse-бренды для Catalog-мутаций.
 */
final readonly class BrandRepository implements BrandRepositoryInterface
{
    /**
     * Возвращает бренд по id или null.
     */
    public function findById(int $id): ?BrandData
    {
        return BrandData::optional(Brand::query()->find($id));
    }

    /**
     * Возвращает первый бренд с таким именем без учёта регистра.
     */
    public function findByName(string $name): ?BrandData
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
