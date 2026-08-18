<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись упаковочных размеров Warehouse через Eloquent-модель Catalog-фичи.
 */
final readonly class PackDimensionCommand implements PackDimensionCommandInterface
{
    /**
     * Создаёт упаковочный размер внутри транзакции.
     *
     * Шаги:
     * 1) Исключить технический id из входного Data.
     * 2) Создать Eloquent-модель каталога внутри транзакции.
     * 3) Вернуть обновлённый Data-снимок созданной записи.
     */
    public function create(PackDimensionData $data): PackDimensionData
    {
        $createPackDimension = fn (): PackDimensionData => PackDimensionData::from(
            PackDimension::query()->create(Arr::except($data->toArray(), ['id', 'type'])),
        );

        return DB::transaction($createPackDimension);
    }

    /**
     * Обновляет упаковочный размер внутри транзакции.
     *
     * Шаги:
     * 1) Найти Eloquent-модель по id из Data.
     * 2) Заполнить изменяемые поля и сохранить запись в транзакции.
     * 3) Вернуть Data-снимок обновлённой модели.
     */
    public function update(PackDimensionData $data): PackDimensionData
    {
        return DB::transaction(function () use ($data): PackDimensionData {
            $packDimension = PackDimension::query()->findOrFail($data->id);
            $packDimension->fill(Arr::except($data->toArray(), ['id', 'type']));
            $packDimension->save();

            return PackDimensionData::from($packDimension->refresh());
        });
    }

    /**
     * Удаляет упаковочный размер и связанные наборы внутри транзакции.
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
     * Удаляет упаковки по id.
     *
     * @param  list<int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            PackDimension::query()->whereKey($ids)->delete();
        });
    }
}
