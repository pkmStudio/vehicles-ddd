<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use Illuminate\Support\Collection;

/**
 * Читает Warehouse-наборы для Catalog-мутаций.
 */
final readonly class KitRepository implements KitRepositoryInterface
{
    /**
     * Возвращает набор по внутреннему идентификатору или null.
     */
    public function findById(int $id): ?KitData
    {
        return $this->findByColumn('id', $id);
    }

    /**
     * Возвращает набор по import_hash или null.
     */
    public function findByImportHash(string $importHash): ?KitData
    {
        return $this->findByColumn('import_hash', $importHash);
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

    private function findByColumn(string $column, int|string $value): ?KitData
    {
        return KitData::optional(
            Kit::query()
                ->where($column, $value)
                ->first(),
        );
    }
}
