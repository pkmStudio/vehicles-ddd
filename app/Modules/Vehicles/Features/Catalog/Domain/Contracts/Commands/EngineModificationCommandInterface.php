<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;

interface EngineModificationCommandInterface
{
    /**
     * Синхронизирует pivot-связи модификации с переданным списком двигателей.
     *
     * Шаги:
     * 1) Получить внутренний id модификации и ids двигателей из Data snapshots.
     * 2) Заменить набор engine_modification связей на переданный список.
     *
     * @param  list<EngineData>  $engines
     */
    public function syncForModification(ModificationData $modification, array $engines): void;

    /**
     * Удаляет pivot-связи engine_modification по внутренним ids.
     *
     * Шаги:
     * 1) Принять список ids pivot-записей.
     * 2) Удалить только найденные связи, не затрагивая engine/modification записи.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void;
}
