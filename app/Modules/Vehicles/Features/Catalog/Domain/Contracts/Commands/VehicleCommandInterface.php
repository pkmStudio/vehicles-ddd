<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;

/**
 * Описывает порт записи автомобилей в каталоге.
 */
interface VehicleCommandInterface
{
    /**
     * Создает запись автомобилей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(VehicleData $data): VehicleData;

    /**
     * Обновляет запись автомобилей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(VehicleData $data): VehicleData;

    /**
     * Удаляет запись автомобилей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    public function deleteByMsId(int $msId): void;

    /**
     * Удаляет автомобили по внутренним ids.
     *
     * Шаги:
     * 1) Принять список внутренних ids автомобилей.
     * 2) Выполнить bulk delete найденных записей после подготовки зависимостей cascade service.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void;
}
