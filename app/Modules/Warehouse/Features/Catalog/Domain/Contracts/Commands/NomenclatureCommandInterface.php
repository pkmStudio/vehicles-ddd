<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;

/**
 * Порт записи Warehouse-номенклатуры.
 */
interface NomenclatureCommandInterface
{
    /**
     * Создаёт номенклатуру и возвращает актуальный снимок.
     */
    public function create(NomenclatureData $data): NomenclatureData;

    /**
     * Обновляет номенклатуру и возвращает актуальный снимок.
     */
    public function update(NomenclatureData $data): NomenclatureData;

    /**
     * Удаляет номенклатуру по id.
     */
    public function deleteById(int $id): void;

    /**
     * Удаляет номенклатуру по ids.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void;
}
