<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface EngineCommandInterface
{
    public function create(EngineData $data): EngineData;

    public function updateByEngId(EngineData $data): EngineData;

    /** Проставить группу двигателю. Принимает Data с обязательными id и groupId. */
    public function setGroupId(EngineData $data): EngineData;
}
