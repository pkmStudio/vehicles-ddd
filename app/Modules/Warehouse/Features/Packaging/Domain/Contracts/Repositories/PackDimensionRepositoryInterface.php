<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Порт чтения упаковочных размеров Warehouse.
 */
interface PackDimensionRepositoryInterface
{
    /**
     * Возвращает все упаковочные размеры выбранного типа.
     * Шаги:
     * 1) Принять warehouse type, для которого подбирается упаковка.
     * 2) Найти все pack dimensions с этим type_id.
     * 3) Вернуть коллекцию Data-снимков для strategy selection.
     *
     * @return Collection<int, PackDimensionData>
     */
    public function byType(TypeData $type): Collection;

    /**
     * Возвращает упаковочный размер по id.
     * Шаги:
     * 1) Принять id упаковочного размера.
     * 2) Найти pack dimension в хранилище Warehouse.
     * 3) Вернуть Data-снимок или null.
     */
    public function findById(int $id): ?PackDimensionData;
}
