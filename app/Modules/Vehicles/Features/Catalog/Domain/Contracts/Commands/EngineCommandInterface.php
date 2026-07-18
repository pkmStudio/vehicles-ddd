<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;

/**
 * Описывает порт записи двигателей в каталоге.
 */
interface EngineCommandInterface
{
    /**
     * Создает запись двигателей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(EngineData $data): EngineData;

    /**
     * Обновляет запись двигателей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(EngineData $data): EngineData;

    /**
     * Удаляет запись двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    public function deleteByEngId(int $engId): void;
}
