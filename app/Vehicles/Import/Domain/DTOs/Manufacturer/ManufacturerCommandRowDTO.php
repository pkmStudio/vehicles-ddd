<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\DTOs\Manufacturer;

final readonly class ManufacturerCommandRowDTO
{
    public function __construct(
        public ?int $mfaId,
        public ?string $name,
    ) {}
}
