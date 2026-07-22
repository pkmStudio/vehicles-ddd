<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;
use Generator;

/**
 * Порт чтения Warehouse-номенклатуры для MoySklad-фичи.
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по id или null.
     */
    public function findById(int $id): ?NomenclatureData;

    /**
     * Итерирует номенклатуру курсором по id.
     *
     * @return Generator<int, NomenclatureData>
     */
    public function cursorById(int $chunk): Generator;
}
