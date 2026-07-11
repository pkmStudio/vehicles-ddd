<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\DTOs\Vehicle;

final readonly class VehicleSheetRowDTO
{
    public function __construct(
        public ?string $excelTableId,
        public ?int $mfaId,
        public ?int $msId,
        public ?string $manufacturerName,
        public ?string $name,
        public ?string $localizedName,
        public ?string $generationShort,
        public ?string $generation,
        public ?int $generationYearFrom,
        public ?int $generationYearTo,
        public ?string $typeCarcase,
        public ?string $type,
        public ?string $provider,
        public ?int $parentMsId,
        public ?string $steeringType,
        public ?bool $isAllow,
    ) {}
}
