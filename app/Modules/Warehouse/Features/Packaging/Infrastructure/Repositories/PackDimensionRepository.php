<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use App\Modules\Warehouse\Features\Packaging\Infrastructure\Models\PackDimension;
use Illuminate\Support\Collection;

/**
 * Читает упаковочные размеры Warehouse для подбора при сборке Kit.
 */
final readonly class PackDimensionRepository implements PackDimensionRepositoryInterface
{
    /**
     * Возвращает все упаковочные размеры выбранного типа.
     *
     * @return Collection<int, PackDimensionData>
     */
    public function byType(TypeData $type): Collection
    {
        $items = PackDimension::query()->where('type_id', $type->id)->get();

        return PackDimensionData::collect($items, Collection::class);
    }

    /**
     * Возвращает упаковочный размер по id или null, если он отсутствует.
     */
    public function findById(int $id): ?PackDimensionData
    {
        $item = PackDimension::query()->find($id);

        return $item === null ? null : PackDimensionData::from($item);
    }
}
