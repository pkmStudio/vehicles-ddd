<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Contracts\Commands;

use App\Vehicles\Application\ModelData\EngineModification\EngineModificationData;

interface EngineModificationCommandInterface
{
    /**
     * Привязывает двигатель к модификации по бизнес-ключам (если оба найдены).
     */
    public function syncWithoutDetaching(EngineModificationData $data): void;
}
