<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Infrastructure\Commands;

use App\Warehouse\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Warehouse\Catalog\Domain\ModelData\BrandData;
use App\Warehouse\Catalog\Infrastructure\Models\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись Warehouse-брендов через Eloquent-модель Catalog-фичи.
 */
final readonly class BrandCommand implements BrandCommandInterface
{
    /**
     * Создаёт бренд внутри транзакции.
     */
    public function create(BrandData $data): BrandData
    {
        return DB::transaction(
            fn (): BrandData => BrandData::from(
                Brand::query()->create(Arr::except($data->toArray(), ['id'])),
            ),
        );
    }

    /**
     * Обновляет бренд внутри транзакции.
     */
    public function update(BrandData $data): BrandData
    {
        return DB::transaction(function () use ($data): BrandData {
            $brand = Brand::query()->findOrFail($data->id);
            $brand->fill(Arr::except($data->toArray(), ['id']));
            $brand->save();

            return BrandData::from($brand->refresh());
        });
    }

    /**
     * Удаляет бренд без каскада внутри транзакции.
     */
    public function deleteById(int $id): void
    {
        DB::transaction(function () use ($id): void {
            Brand::query()->whereKey($id)->delete();
        });
    }
}
