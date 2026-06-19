<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\ModelData\Modification;

final readonly class ModificationData
{
    public function __construct(
        public int $modId,
        public string $type,
        public int $vehicleId,
        public int $msId,
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public ?string $description = null,
        public ?int $powerPs = null,
        public ?int $powerKw = null,
        public ?string $engineType = null,
        public ?string $gearType = null,
        public ?string $driveType = null,
        public ?string $brakeSystemType = null,
        public ?int $numberOfCylinders = null,
        public ?float $capacityLt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'mod_id' => $this->modId,
            'type' => $this->type,
            'vehicle_id' => $this->vehicleId,
            'ms_id' => $this->msId,
            'year_from' => $this->yearFrom,
            'year_to' => $this->yearTo,
            'description' => $this->description,
            'power_ps' => $this->powerPs,
            'power_kw' => $this->powerKw,
            'engine_type' => $this->engineType,
            'gear_type' => $this->gearType,
            'drive_type' => $this->driveType,
            'brake_system_type' => $this->brakeSystemType,
            'number_of_cylinders' => $this->numberOfCylinders,
            'capacity_lt' => $this->capacityLt,
        ];
    }
}
