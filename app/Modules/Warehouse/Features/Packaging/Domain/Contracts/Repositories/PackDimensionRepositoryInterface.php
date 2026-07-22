<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Порт чтения упаковочных размеров Warehouse.
 */
interface PackDimensionRepositoryInterface
{
    /**
     * Возвращает все упаковочные размеры выбранного типа.
     *
     * @return Collection<int, PackDimensionData>
     */
    public function byType(TypeData $type): Collection;

    /**
     * Возвращает упаковочный размер по id.
     */
    public function findById(int $id): ?PackDimensionData;
}
