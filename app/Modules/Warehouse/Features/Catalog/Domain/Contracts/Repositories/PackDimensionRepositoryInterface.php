<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;
use Illuminate\Support\Collection;

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

    /**
     * Возвращает упаковки по id, индексированные по id.
     *
     * Шаги:
     * 1. Принять список внутренних id упаковок.
     * 2. Вернуть найденные `PackDimensionData`, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, PackDimensionData>
     */
    public function findByIds(array $ids): Collection;
}
