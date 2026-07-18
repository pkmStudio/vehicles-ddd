<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface EngineCommandInterface
{
    /** Upsert по натуральному ключу eng_id. */
    public function upsertByEngId(EngineData $data): EngineData;

    /** Проставить группу двигателю. Принимает Data с обязательными id и groupId. */
    public function setGroupId(EngineData $data): EngineData;
}
