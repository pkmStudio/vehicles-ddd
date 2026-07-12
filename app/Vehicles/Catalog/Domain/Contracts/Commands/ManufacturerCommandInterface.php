<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Commands;

use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;

/**
 * Описывает порт записи производителей в каталоге.
 */
interface ManufacturerCommandInterface
{
    /**
     * Создает запись производителей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(ManufacturerData $data): ManufacturerData;

    /**
     * Обновляет запись производителей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(ManufacturerData $data): ManufacturerData;

    /**
     * Удаляет запись производителей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    public function deleteByMfaId(int $mfaId): void;
}
