<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;

/**
 * Порт чтения упаковочных размеров для Import-фичи.
 */
interface PackDimensionRepositoryInterface
{
    /**
     * Возвращает упаковочный размер по id или null.
     */
    public function findById(int $id): ?PackDimensionData;
}
