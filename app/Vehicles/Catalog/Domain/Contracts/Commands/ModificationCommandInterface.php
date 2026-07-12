<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Commands;

use App\Vehicles\Catalog\Domain\ModelData\ModificationData;

/**
 * Описывает порт записи модификаций в каталоге.
 */
interface ModificationCommandInterface
{
    /**
     * Создает запись модификаций.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(ModificationData $data): ModificationData;

    /**
     * Обновляет запись модификаций.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(ModificationData $data): ModificationData;

    /**
     * Удаляет запись модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    public function deleteByModIdAndType(int $modId, string $type): void;
}
