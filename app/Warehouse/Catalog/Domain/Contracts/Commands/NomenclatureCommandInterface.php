<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Commands;

use App\Warehouse\Catalog\Domain\ModelData\NomenclatureData;

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
     * Удаляет номенклатуру по id без каскада.
     */
    public function deleteById(int $id): void;
}
