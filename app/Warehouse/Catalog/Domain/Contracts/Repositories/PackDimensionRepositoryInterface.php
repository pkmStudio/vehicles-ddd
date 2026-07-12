<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Repositories;

use App\Warehouse\Catalog\Domain\DTOs\PackDimension\PackDimensionDeletionBlockersDTO;
use App\Warehouse\Catalog\Domain\ModelData\PackDimensionData;

/**
 * Порт чтения упаковочных размеров Warehouse для Catalog-мутаций.
 */
interface PackDimensionRepositoryInterface
{
    /**
     * Возвращает упаковочный размер по id или null.
     */
    public function find(int $id): ?PackDimensionData;

    /**
     * Собирает зависимости, блокирующие удаление упаковочного размера.
     */
    public function deletionBlockers(int $id): ?PackDimensionDeletionBlockersDTO;
}
