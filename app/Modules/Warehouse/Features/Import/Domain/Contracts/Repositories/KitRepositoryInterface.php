<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;

/**
 * Порт чтения Warehouse-наборов для Import-фичи.
 */
interface KitRepositoryInterface
{
    /**
     * Возвращает набор по id или null.
     */
    public function findById(int $id): ?KitData;

    /**
     * Возвращает набор по import hash или null.
     */
    public function findByImportHash(string $importHash): ?KitData;
}
