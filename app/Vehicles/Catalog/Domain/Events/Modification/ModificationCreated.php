<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Modification;

use App\Vehicles\Catalog\Domain\ModelData\ModificationData;

final readonly class ModificationCreated
{
    public function __construct(public int $userId, public string $operationId, public ModificationData $modification) {}
}
