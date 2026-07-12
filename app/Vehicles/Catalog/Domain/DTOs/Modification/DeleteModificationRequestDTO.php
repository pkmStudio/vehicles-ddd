<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Modification;

use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

final readonly class DeleteModificationRequestDTO
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $modId,
        public VehicleTypeEnum $type,
    ) {}
}
