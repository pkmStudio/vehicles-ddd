<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\PackDimension;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Пишет упаковочный размер Warehouse через Eloquent-копию модели Import-фичи.
 */
final readonly class PackDimensionCommand implements PackDimensionCommandInterface
{
    /**
     * Обновляет упаковочный размер по id.
     */
    public function updateById(PackDimensionData $data): PackDimensionData
    {
        $values = Arr::except($data->toArray(), ['id']);
        $packDimension = $data->id === null ? null : PackDimension::query()->find($data->id);

        if ($packDimension === null) {
            throw new RuntimeException("Упаковочный размер с ID {$data->id} не найден");
        }

        $packDimension->update($values);

        return PackDimensionData::from($packDimension->refresh());
    }

    /**
     * Создаёт новый упаковочный размер.
     */
    public function create(PackDimensionData $data): PackDimensionData
    {
        $values = Arr::except($data->toArray(), ['id']);

        return PackDimensionData::from(PackDimension::query()->create($values));
    }
}
