<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Infrastructure\Repositories;

use App\Warehouse\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Warehouse\Catalog\Domain\ModelData\KitData;
use App\Warehouse\Catalog\Infrastructure\Models\Kit;

/**
 * Читает Warehouse-наборы для Catalog-мутаций.
 */
final readonly class KitRepository implements KitRepositoryInterface
{
    /**
     * Возвращает набор по id или null.
     */
    public function find(int $id): ?KitData
    {
        return KitData::optional(Kit::query()->find($id));
    }

    /**
     * Возвращает первый набор с таким import_hash или null.
     */
    public function firstByImportHash(string $importHash): ?KitData
    {
        $kit = Kit::query()
            ->where('import_hash', $importHash)
            ->first();

        return KitData::optional($kit);
    }
}
