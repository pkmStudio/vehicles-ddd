<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Manufacturer;

final readonly class DeleteManufacturerRequestDTO
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $mfaId,
    ) {}
}
