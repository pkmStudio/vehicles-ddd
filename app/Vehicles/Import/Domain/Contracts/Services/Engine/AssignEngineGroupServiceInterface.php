<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Engine;

use App\Vehicles\Import\Domain\DTOs\Engine\AssignEngineGroupResultDTO;

interface AssignEngineGroupServiceInterface
{
    public function assignGroup(string $code, int $groupId): AssignEngineGroupResultDTO;
}
