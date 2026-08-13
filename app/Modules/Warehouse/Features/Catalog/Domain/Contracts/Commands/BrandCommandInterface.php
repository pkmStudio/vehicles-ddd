<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;

/**
 * Порт записи Warehouse-брендов.
 */
interface BrandCommandInterface
{
    /**
     * Создаёт бренд и возвращает актуальный снимок.
     *
     * Шаги:
     * 1) Исключить технический id из входного Data.
     * 2) Создать Eloquent-модель каталога внутри транзакции.
     * 3) Вернуть обновлённый Data-снимок созданной записи.
     */
    public function create(BrandData $data): BrandData;

    /**
     * Обновляет бренд и возвращает актуальный снимок.
     *
     * Шаги:
     * 1) Найти Eloquent-модель по id из Data.
     * 2) Заполнить изменяемые поля и сохранить запись в транзакции.
     * 3) Вернуть Data-снимок обновлённой модели.
     */
    public function update(BrandData $data): BrandData;

    /**
     * Удаляет бренд по id.
     *
     * Шаги:
     * 1) Принять идентификатор или список идентификаторов каталога.
     * 2) Выполнить удаление Eloquent-записей внутри транзакции.
     * 3) Завершить без возврата бизнес-данных.
     */
    public function deleteById(int $id): void;
}
