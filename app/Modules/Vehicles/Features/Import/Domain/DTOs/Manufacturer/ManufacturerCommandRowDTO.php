<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer;

final readonly class ManufacturerCommandRowDTO
{
    public function __construct(
        public ?int $mfaId,
        public ?string $name,
    ) {}
}
