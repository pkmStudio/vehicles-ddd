<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;

/**
 * Порт записи Warehouse-набора (Kit) и его состава.
 */
interface KitCommandInterface
{
    /**
     * Обновляет набор и его состав из import data.
     *
     * Шаги:
     * 1) Найти существующий Kit по id из DTO.
     * 2) Обновить поля набора.
     * 3) Пересобрать pivot-состав по переданным id номенклатур с порядком.
     * 4) Вернуть актуальный снимок KitData.
     *
     * @param  array<int, int>  $nomenclatureIds  id номенклатур в порядке артикулов строки —
     *                                            порядок становится `sort` в pivot-таблице.
     */
    public function update(KitData $data, array $nomenclatureIds): KitData;

    /**
     * Создаёт набор и его состав.
     *
     * Шаги:
     * 1) Создать Kit из DTO.
     * 2) Привязать номенклатуры к набору в порядке строки импорта.
     * 3) Вернуть снимок созданного KitData.
     *
     * @param  array<int, int>  $nomenclatureIds  id номенклатур в порядке артикулов строки —
     *                                            порядок становится `sort` в pivot-таблице.
     */
    public function create(KitData $data, array $nomenclatureIds): KitData;
}
