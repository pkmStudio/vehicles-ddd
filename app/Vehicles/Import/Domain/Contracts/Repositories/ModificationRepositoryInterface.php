<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\ModificationData;

/**
 * Чтение Modification (read-only).
 */
interface ModificationRepositoryInterface
{
    /**
     * Модификация по натуральному ключу (ms_id + mod_id), имеющая двигатели, с загруженными
     * engines (ModificationData::$engines заполнен).
     */
    public function firstByMsIdAndModIdWithEngines(int $msId, int $modId): ?ModificationData;
}
