<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Repositories;

use App\Warehouse\Catalog\Domain\ModelData\KitData;

/**
 * Порт чтения Warehouse-наборов для Catalog-мутаций.
 */
interface KitRepositoryInterface
{
    /**
     * Возвращает набор по id или null.
     */
    public function find(int $id): ?KitData;

    /**
     * Возвращает первый набор с таким import_hash или null.
     */
    public function firstByImportHash(string $importHash): ?KitData;
}
