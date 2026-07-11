<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\DTOs\Vehicle;

final readonly class VehicleWiperSheetRowDTO
{
    public function __construct(
        public ?int $msId,
        public ?string $templateSlug,
        public ?string $featureValueName,
        public ?string $name,
        public ?string $text,
        public array $details,
    ) {}
}
