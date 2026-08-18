<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\BrandCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись Warehouse-брендов через Eloquent-модель Catalog-фичи.
 */
final readonly class BrandCommand implements BrandCommandInterface
{
    /**
     * Создаёт бренд внутри транзакции.
     *
     * Шаги:
     * 1) Исключить технический id из входного Data.
     * 2) Создать Eloquent-модель каталога внутри транзакции.
     * 3) Вернуть обновлённый Data-снимок созданной записи.
     */
    public function create(BrandData $data): BrandData
    {
        $createBrand = fn (): BrandData => BrandData::from(
            Brand::query()->create(Arr::except($data->toArray(), ['id'])),
        );

        return DB::transaction($createBrand);
    }

    /**
     * Обновляет бренд внутри транзакции.
     *
     * Шаги:
     * 1) Найти Eloquent-модель по id из Data.
     * 2) Заполнить изменяемые поля и сохранить запись в транзакции.
     * 3) Вернуть Data-снимок обновлённой модели.
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
     *
     * Шаги:
     * 1) Принять идентификатор или список идентификаторов каталога.
     * 2) Выполнить удаление Eloquent-записей внутри транзакции.
     * 3) Завершить без возврата бизнес-данных.
     */
    public function deleteById(int $id): void
    {
        $this->deleteByIds([$id]);
    }

    /**
     * Удаляет бренды по id.
     *
     * @param  list<int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            Brand::query()->whereKey($ids)->delete();
        });
    }
}
