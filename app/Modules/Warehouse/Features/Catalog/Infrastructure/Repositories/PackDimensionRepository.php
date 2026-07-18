<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionDeletionBlockersDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;

/**
 * Читает упаковочные размеры Warehouse для Catalog-мутаций.
 */
final readonly class PackDimensionRepository implements PackDimensionRepositoryInterface
{
    /**
     * Возвращает упаковочный размер по id или null.
     */
    public function find(int $id): ?PackDimensionData
    {
        $packDimension = PackDimension::query()
            ->with('type')
            ->find($id);

        return PackDimensionData::optional($packDimension);
    }

    /**
     * Собирает зависимости, блокирующие удаление упаковочного размера.
     */
    public function deletionBlockers(int $id): ?PackDimensionDeletionBlockersDTO
    {
        if (! PackDimension::query()->whereKey($id)->exists()) {
            return null;
        }

        return new PackDimensionDeletionBlockersDTO(
            kitsCount: Kit::query()->where('pack_dimension_id', $id)->count(),
        );
    }
}
