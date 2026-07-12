<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Modification;

use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

final readonly class ModificationDeleted
{
    public function __construct(public int $userId, public string $operationId, public int $modId, public VehicleTypeEnum $type, public int $modificationId) {}
}
