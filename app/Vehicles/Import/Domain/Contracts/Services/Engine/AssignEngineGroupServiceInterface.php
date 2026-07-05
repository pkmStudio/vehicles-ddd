<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Engine;

use App\Vehicles\Import\Domain\DTOs\AssignEngineGroupResult;

interface AssignEngineGroupServiceInterface
{
    public function assignGroup(string $code, int $groupId): AssignEngineGroupResult;
}
