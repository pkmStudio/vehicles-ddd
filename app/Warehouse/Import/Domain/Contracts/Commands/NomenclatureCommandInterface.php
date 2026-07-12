<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Commands;

use App\Warehouse\Import\Domain\ModelData\NomenclatureData;

/**
 * Порт записи Warehouse-номенклатуры.
 */
interface NomenclatureCommandInterface
{
    /**
     * Обновляет запись по id. Бросает исключение, если запись с этим id не найдена.
     */
    public function updateById(NomenclatureData $data): NomenclatureData;

    /**
     * Создаёт запись либо обновляет существующую по уникальному part_number.
     */
    public function upsertByPartNumber(NomenclatureData $data): NomenclatureData;
}
