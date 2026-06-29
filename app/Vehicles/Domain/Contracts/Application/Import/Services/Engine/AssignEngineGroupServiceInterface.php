<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Engine;

use App\Vehicles\Domain\DTOs\AssignEngineGroupResult;

interface AssignEngineGroupServiceInterface
{
    public function execute(string $code, int $groupId): AssignEngineGroupResult;
}
