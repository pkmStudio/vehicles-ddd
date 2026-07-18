<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\AssignEngineGroupResultDTO;

interface AssignEngineGroupServiceInterface
{
    public function assignGroup(string $code, int $groupId): AssignEngineGroupResultDTO;
}
