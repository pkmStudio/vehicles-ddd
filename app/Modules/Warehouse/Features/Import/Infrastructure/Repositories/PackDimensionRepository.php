<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\PackDimension;

/**
 * Читает упаковочные размеры Warehouse для Import-фичи.
 */
final readonly class PackDimensionRepository implements PackDimensionRepositoryInterface
{
    /**
     * Возвращает упаковочный размер по id или null.
     *
     * Шаги:
     * 1) Найти PackDimension-модель по первичному ключу.
     * 2) Преобразовать найденную модель в PackDimensionData.
     * 3) Вернуть null, если модель отсутствует.
     */
    public function findById(int $id): ?PackDimensionData
    {
        $packDimension = PackDimension::query()->find($id);

        return PackDimensionData::optional($packDimension);
    }
}
