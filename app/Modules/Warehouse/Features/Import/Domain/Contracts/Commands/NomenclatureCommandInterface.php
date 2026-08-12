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
     *
     * Шаги:
     * 1) Найти номенклатуру по id из DTO.
     * 2) Применить импортируемые поля и details.
     * 3) Вернуть актуальный снимок NomenclatureData.
     */
    public function updateById(NomenclatureData $data): NomenclatureData;

    /**
     * Создаёт новую номенклатуру.
     *
     * Шаги:
     * 1) Собрать payload записи из DTO.
     * 2) Создать новую запись без внешнего id.
     * 3) Вернуть снимок созданной NomenclatureData.
     */
    public function create(NomenclatureData $data): NomenclatureData;

    /**
     * Создаёт новую номенклатуру с явно заданным id (импорт из внешней системы, где id уже
     * назначен, но записи с ним ещё нет в этой БД).
     *
     * Шаги:
     * 1) Собрать payload записи из DTO вместе с id.
     * 2) Создать запись с явно заданным id.
     * 3) Вернуть снимок созданной NomenclatureData.
     */
    public function createWithId(NomenclatureData $data): NomenclatureData;
}
