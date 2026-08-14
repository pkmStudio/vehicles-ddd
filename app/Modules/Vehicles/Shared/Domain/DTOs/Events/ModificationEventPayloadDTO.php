<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Events;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

final readonly class ModificationEventPayloadDTO
{
    /**
     * @param  array<int, string>  $allowChangeFields
     */
    public function __construct(
        public int $id,
        public int $modId,
        public VehicleTypeEnum $type,
        public int $vehicleId,
        public int $msId,
        public ProviderEnum $provider,
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public ?string $description = null,
        public ?string $descriptionShort = null,
        public ?string $localizedName = null,
        public ?int $powerPs = null,
        public ?int $powerKw = null,
        public ?EngineTypeEnum $engineType = null,
        public ?GearTypeEnum $gearType = null,
        public ?DriveTypeEnum $driveType = null,
        public ?BrakeSystemTypeEnum $brakeSystemType = null,
        public ?int $numberOfCylinders = null,
        public ?float $capacityLt = null,
        public array $allowChangeFields = [],
    ) {}
}
