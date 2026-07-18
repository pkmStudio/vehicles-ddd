<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification;

final readonly class ModificationCommandRowDTO
{
    public function __construct(
        public ?int $msId,
        public ?int $modId,
        public ?int $yearFrom,
        public ?int $yearTo,
        public ?string $description,
        public ?int $powerPs,
        public ?int $powerKw,
        public ?string $engineType,
        public ?string $gearType,
        public ?string $driveType,
        public ?string $brakeSystemType,
        public ?int $numberOfCylinders,
        public ?float $capacityLt,
        public ?string $type,
    ) {}
}
