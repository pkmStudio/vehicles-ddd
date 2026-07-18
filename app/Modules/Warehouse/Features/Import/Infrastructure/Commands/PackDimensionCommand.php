<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\PackDimension;
use Illuminate\Support\Arr;

/**
 * Пишет упаковочный размер Warehouse через Eloquent-копию модели Import-фичи.
 */
final readonly class PackDimensionCommand implements PackDimensionCommandInterface
{
    /**
     * Обновляет запись по id, если она существует, иначе создаёт новую.
     */
    public function upsertById(PackDimensionData $data): PackDimensionData
    {
        $values = Arr::except($data->toArray(), ['id']);

        if ($data->id !== null) {
            $existing = PackDimension::query()->find($data->id);

            if ($existing !== null) {
                $existing->update($values);

                return PackDimensionData::from($existing->refresh());
            }
        }

        return PackDimensionData::from(PackDimension::query()->create($values));
    }
}
