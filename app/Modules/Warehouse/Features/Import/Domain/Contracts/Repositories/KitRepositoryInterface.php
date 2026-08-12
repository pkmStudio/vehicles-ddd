<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;

/**
 * Порт чтения Warehouse-наборов для Import-фичи.
 */
interface KitRepositoryInterface
{
    /**
     * Возвращает набор по id или null.
     *
     * Шаги:
     * 1) Выполнить чтение Kit по первичному ключу.
     * 2) Вернуть KitData, если запись найдена.
     * 3) Вернуть null, если записи нет.
     */
    public function findById(int $id): ?KitData;

    /**
     * Возвращает набор по import hash или null.
     *
     * Шаги:
     * 1) Выполнить чтение Kit по import_hash.
     * 2) Вернуть KitData для найденного набора.
     * 3) Вернуть null, если такого состава ещё нет.
     */
    public function findByImportHash(string $importHash): ?KitData;
}
