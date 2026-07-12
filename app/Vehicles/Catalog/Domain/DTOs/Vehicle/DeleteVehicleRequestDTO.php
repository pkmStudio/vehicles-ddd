<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Vehicle;

final readonly class DeleteVehicleRequestDTO
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $msId,
    ) {}
}
