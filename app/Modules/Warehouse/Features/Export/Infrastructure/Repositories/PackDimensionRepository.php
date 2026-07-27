<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Export\Infrastructure\Models\PackDimension;
use Illuminate\Support\Collection;

/**
 * Читает упаковочные размеры Warehouse для экспорта.
 */
final readonly class PackDimensionRepository implements PackDimensionRepositoryInterface
{
    /**
     * @return Collection<int, PackDimensionData>
     */
    public function all(): Collection
    {
        $packDimensions = PackDimension::query()
            ->with('type')
            ->orderBy('id')
            ->get();

        return PackDimensionData::collect($packDimensions, Collection::class);
    }
}
