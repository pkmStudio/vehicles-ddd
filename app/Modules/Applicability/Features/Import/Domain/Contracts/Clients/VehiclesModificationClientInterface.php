<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Clients;

interface VehiclesModificationClientInterface
{
    /**
     * Разрешает внешние идентификаторы модели и модификации в локальный modification id.
     *
     * Шаги:
     * 1. Передает пару `ms_id` и `mod_id` во внешний Vehicles boundary.
     * 2. Возвращает локальный id модификации для записи применяемости.
     * 3. Оставляет обработку отсутствующей модификации реализации client-а.
     */
    public function resolveByMsAndModId(int $msId, int $modId): int;
}
