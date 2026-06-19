<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Commands;

use App\Vehicles\Domain\ModelData\EngineModification\EngineModificationData;

interface EngineModificationCommandInterface
{
    /**
     * Привязывает двигатель к модификации по бизнес-ключам (если оба найдены).
     */
    public function syncWithoutDetaching(EngineModificationData $data): void;
}
