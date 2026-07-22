<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Kit;

/**
 * Читает Warehouse-наборы для Import-фичи.
 */
final readonly class KitRepository implements KitRepositoryInterface
{
    /**
     * Возвращает набор по id или null.
     */
    public function findById(int $id): ?KitData
    {
        $kit = Kit::query()->find($id);

        return KitData::optional($kit);
    }

    /**
     * Возвращает набор по import hash или null.
     */
    public function findByImportHash(string $importHash): ?KitData
    {
        $kit = Kit::query()
            ->where('import_hash', $importHash)
            ->first();

        return KitData::optional($kit);
    }
}
