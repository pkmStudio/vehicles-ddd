<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;

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
