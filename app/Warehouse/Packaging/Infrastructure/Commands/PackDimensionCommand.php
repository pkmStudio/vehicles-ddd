<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Infrastructure\Commands;

use App\Warehouse\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Infrastructure\Models\PackDimension;
use Illuminate\Support\Arr;

/**
 * Создаёт сгенерированные упаковочные размеры Warehouse через Eloquent-копию модели фичи.
 */
final readonly class PackDimensionCommand implements PackDimensionCommandInterface
{
    /**
     * Создаёт новый упаковочный размер и возвращает сохранённую запись.
     */
    public function create(PackDimensionData $data): PackDimensionData
    {
        $packDimension = PackDimension::query()->create(Arr::except($data->toArray(), ['id']));

        return PackDimensionData::from($packDimension);
    }
}
