<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Domain\Contracts\Repositories;

use App\Warehouse\MoySklad\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-номенклатуры для MoySklad-фичи.
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по id или null.
     */
    public function find(int $id): ?NomenclatureData;

    /**
     * Итерирует номенклатуру чанками по id.
     *
     * @param  callable(Collection<int, NomenclatureData>): void  $callback
     */
    public function chunkById(int $chunk, callable $callback): void;
}
