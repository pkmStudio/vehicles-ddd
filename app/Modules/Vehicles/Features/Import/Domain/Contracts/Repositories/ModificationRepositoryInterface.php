<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

/**
 * Чтение Modification (read-only).
 */
interface ModificationRepositoryInterface
{
    /**
     * Модификация по натуральному ключу upsert-операции импорта.
     *
     * Шаги:
     * 1) Выполнить read query по mod_id и type.
     * 2) Вернуть ModificationData или null.
     */
    public function findByModIdAndType(int $modId, string $type): ?ModificationData;

    /**
     * Модификация по натуральному ключу (ms_id + mod_id), имеющая двигатели, с загруженными
     * engines (ModificationData::$engines заполнен).
     *
     * Шаги:
     * 1) Выполнить read query по ms_id и mod_id с фильтром наличия engines.
     * 2) Eager-load engine snapshots для результата.
     * 3) Вернуть ModificationData или null.
     */
    public function findByMsIdAndModIdWithEngines(int $msId, int $modId): ?ModificationData;
}
