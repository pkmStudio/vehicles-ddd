<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\UseCases\Engine;

use App\Vehicles\Domain\DTOs\AssignEngineGroupResult;

interface AssignEngineGroupUseCaseInterface
{
    public function execute(string $code, int $groupId): AssignEngineGroupResult;
}
