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
     *
     * Шаги:
     * 1) Исключить технический id из входного Data.
     * 2) Создать Eloquent-модель каталога внутри транзакции.
     * 3) Вернуть обновлённый Data-снимок созданной записи.
     */
    public function create(NomenclatureData $data): NomenclatureData;

    /**
     * Обновляет номенклатуру и возвращает актуальный снимок.
     *
     * Шаги:
     * 1) Найти Eloquent-модель по id из Data.
     * 2) Заполнить изменяемые поля и сохранить запись в транзакции.
     * 3) Вернуть Data-снимок обновлённой модели.
     */
    public function update(NomenclatureData $data): NomenclatureData;

    /**
     * Удаляет номенклатуру по id.
     *
     * Шаги:
     * 1) Принять идентификатор или список идентификаторов каталога.
     * 2) Выполнить удаление Eloquent-записей внутри транзакции.
     * 3) Завершить без возврата бизнес-данных.
     */
    public function deleteById(int $id): void;

    /**
     * Удаляет номенклатуру по ids.
     *
     * @param  array<int, int>  $ids
     *
     * Шаги:
     * 1) Принять идентификатор или список идентификаторов каталога.
     * 2) Выполнить удаление Eloquent-записей внутри транзакции.
     * 3) Завершить без возврата бизнес-данных.
     */
    public function deleteByIds(array $ids): void;
}
