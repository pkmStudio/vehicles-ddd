<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineModificationData;

interface EngineModificationCommandInterface
{
    /**
     * Привязывает двигатель к модификации по бизнес-ключам (если оба найдены).
     */
    public function syncWithoutDetaching(EngineModificationData $data): void;
}
