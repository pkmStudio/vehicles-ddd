<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-наборов для Catalog-мутаций.
 */
interface KitRepositoryInterface
{
    /**
     * Возвращает набор по внутреннему идентификатору или null.
     */
    public function findById(int $id): ?KitData;

    /**
     * Возвращает набор по import_hash или null.
     */
    public function findByImportHash(string $importHash): ?KitData;

    /**
     * Возвращает ids наборов упаковочного размера.
     *
     * @return Collection<int, int>
     */
    public function findIdsByPackDimensionId(int $packDimensionId): Collection;
}
