<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;

/**
 * Описывает порт записи спецификаций деталей в каталоге.
 */
interface PartSpecificationCommandInterface
{
    /**
     * Создает запись спецификации детали.
     *
     * Шаги:
     * 1) Записать PartSpecificationData в catalog storage.
     * 2) Вернуть актуальный Data snapshot созданной записи.
     */
    public function create(PartSpecificationData $data): PartSpecificationData;

    /**
     * Обновляет запись спецификации детали.
     *
     * Шаги:
     * 1) Найти существующую specification по id из Data snapshot.
     * 2) Сохранить owner/template/details и optional поля.
     * 3) Вернуть актуальный Data snapshot обновленной записи.
     */
    public function update(PartSpecificationData $data): PartSpecificationData;

    /**
     * Удаляет запись спецификации детали по id.
     *
     * Шаги:
     * 1) Найти specification по внутреннему id.
     * 2) Удалить найденную запись без cascade за пределами этого command.
     */
    public function deleteById(int $id): void;

    /**
     * Удаляет несколько спецификаций по внутренним ids.
     *
     * Шаги:
     * 1) Принять список внутренних ids спецификаций.
     * 2) Выполнить bulk delete найденных записей.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void;
}
