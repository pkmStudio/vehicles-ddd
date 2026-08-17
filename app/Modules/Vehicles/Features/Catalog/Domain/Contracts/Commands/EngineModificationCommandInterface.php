<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification\ModificationEngineLinkDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;

interface EngineModificationCommandInterface
{
    /**
     * Синхронизирует pivot-связи модификации с переданным списком связей.
     *
     * Шаги:
     * 1) Получить внутренний id модификации и ids двигателей из link snapshots.
     * 2) Заменить набор engine_modification связей на переданный список.
     * 3) Записать владельца каждой связи в `engine_modification.provider`.
     *
     * @param  list<ModificationEngineLinkDTO>  $links
     */
    public function syncForModification(ModificationData $modification, array $links): void;

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
