<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись Warehouse-брендов через Eloquent-модель Catalog-фичи.
 */
final readonly class BrandCommand implements BrandCommandInterface
{
    public function __construct(
        private NomenclatureCommandInterface $nomenclatures,
    ) {}

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
     * Удаляет бренд и связанные номенклатуры внутри транзакции.
     */
    public function deleteById(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $nomenclatureIds = Nomenclature::query()
                ->where('brand_id', $id)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $this->nomenclatures->deleteByIds($nomenclatureIds);
            Brand::query()->whereKey($id)->delete();
        });
    }
}
