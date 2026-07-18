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
     */
    public function create(PackDimensionData $data): PackDimensionData
    {
        return DB::transaction(
            fn (): PackDimensionData => PackDimensionData::from(
                PackDimension::query()->create(Arr::except($data->toArray(), ['id', 'type'])),
            ),
        );
    }

    /**
     * Обновляет упаковочный размер внутри транзакции.
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
     * Удаляет упаковочный размер без каскада внутри транзакции.
     */
    public function deleteById(int $id): void
    {
        DB::transaction(function () use ($id): void {
            PackDimension::query()->whereKey($id)->delete();
        });
    }
}
