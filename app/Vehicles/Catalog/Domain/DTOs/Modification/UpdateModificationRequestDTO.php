<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Modification;

use App\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

final readonly class UpdateModificationRequestDTO
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $modId,
        public int $msId,
        public VehicleTypeEnum $type,
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public ?string $description = null,
        public ?int $powerPs = null,
        public ?int $powerKw = null,
        public ?EngineTypeEnum $engineType = null,
        public ?GearTypeEnum $gearType = null,
        public ?DriveTypeEnum $driveType = null,
        public ?BrakeSystemTypeEnum $brakeSystemType = null,
        public ?int $numberOfCylinders = null,
        public ?float $capacityLt = null,
    ) {}
}
