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
     * 2) Добавить связь без удаления существующих связей.
     */
    public function syncWithoutDetaching(EngineModificationData $data): void;
}
