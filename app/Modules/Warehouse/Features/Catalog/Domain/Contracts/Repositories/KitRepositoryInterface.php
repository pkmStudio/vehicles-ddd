<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;

/**
 * Порт чтения Warehouse-наборов для Catalog-мутаций.
 */
interface KitRepositoryInterface
{
    /**
     * Возвращает набор по id или null.
     */
    public function findById(int $id): ?KitData;

    /**
     * Возвращает первый набор с таким import_hash или null.
     */
    public function findByImportHash(string $importHash): ?KitData;
}
