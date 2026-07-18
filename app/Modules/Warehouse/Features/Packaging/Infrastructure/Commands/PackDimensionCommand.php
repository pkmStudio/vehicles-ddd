<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Infrastructure\Models\PackDimension;
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
