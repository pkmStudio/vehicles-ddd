<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;

/**
 * Порт записи Warehouse-наборов и их состава.
 */
interface KitCommandInterface
{
    /**
     * Создаёт набор и полностью записывает его состав.
     *
     * @param  array<int, int>  $nomenclatureIds
     *
     * Шаги:
     * 1) Исключить технический id из входного Data.
     * 2) Создать Eloquent-модель каталога внутри транзакции.
     * 3) Вернуть обновлённый Data-снимок созданной записи.
     */
    public function create(KitData $data, array $nomenclatureIds): KitData;

    /**
     * Обновляет набор и полностью переписывает его состав.
     *
     * @param  array<int, int>  $nomenclatureIds
     *
     * Шаги:
     * 1) Найти Eloquent-модель по id из Data.
     * 2) Заполнить изменяемые поля и сохранить запись в транзакции.
     * 3) Вернуть Data-снимок обновлённой модели.
     */
    public function update(KitData $data, array $nomenclatureIds): KitData;

    /**
     * Удаляет набор и его pivot-состав вручную.
     *
     * Шаги:
     * 1) Принять идентификатор или список идентификаторов каталога.
     * 2) Выполнить удаление Eloquent-записей внутри транзакции.
     * 3) Завершить без возврата бизнес-данных.
     */
    public function deleteById(int $id): void;

    /**
     * Удаляет наборы и их pivot-состав вручную.
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
