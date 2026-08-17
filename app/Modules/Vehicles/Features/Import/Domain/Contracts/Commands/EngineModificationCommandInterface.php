<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineModificationData;

interface EngineModificationCommandInterface
{
    /**
     * Привязывает двигатель к модификации по бизнес-ключам (если оба найдены).
     *
     * Шаги:
     * 1) Найти modification и engine по ключам из DTO.
     * 2) Если такая связь уже существует, оставить ее без изменения provider.
     * 3) Если связи нет, добавить ее без удаления существующих связей.
     */
    public function attachIfMissing(EngineModificationData $data): void;
}
