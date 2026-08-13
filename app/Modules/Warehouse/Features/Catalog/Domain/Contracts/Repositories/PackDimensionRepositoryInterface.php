<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;

/**
 * Порт чтения упаковочных размеров Warehouse для Catalog-мутаций.
 */
interface PackDimensionRepositoryInterface
{
    /**
     * Возвращает упаковочный размер по id или null.
     *
     * Шаги:
     * 1. Принять внутренний id упаковочного размера.
     * 2. Вернуть `PackDimensionData` или `null`, если запись не найдена.
     */
    public function findById(int $id): ?PackDimensionData;
}
