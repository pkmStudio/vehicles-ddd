<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitLookupDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use Illuminate\Support\Collection;

/**
 * Читает Warehouse-наборы для Catalog-мутаций.
 */
final readonly class KitRepository implements KitRepositoryInterface
{
    /**
     * Возвращает набор по typed lookup-критерию или null.
     */
    public function find(KitLookupDTO $lookup): ?KitData
    {
        $query = Kit::query();

        if ($lookup->id !== null) {
            return KitData::optional($query->find($lookup->id));
        }

        $kit = $query
            ->where('import_hash', $lookup->importHash)
            ->first();

        return KitData::optional($kit);
    }

    /**
     * Возвращает ids наборов упаковочного размера.
     *
     * @return Collection<int, int>
     */
    public function findIdsByPackDimensionId(int $packDimensionId): Collection
    {
        return Kit::query()
            ->where('pack_dimension_id', $packDimensionId)
            ->pluck('id')
            ->map($this->toInteger(...))
            ->values();
    }

    private function toInteger(mixed $id): int
    {
        return (int) $id;
    }
}
