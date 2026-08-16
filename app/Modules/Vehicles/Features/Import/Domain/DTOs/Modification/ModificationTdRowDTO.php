<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

final readonly class ModificationTdRowDTO
{
    /**
     * Фиксирует строку command-импорта модификации из TecDoc cascade.
     */
    public function __construct(
        public int $msId,
        public int $modId,
        public int $yearFrom,
        public ?int $yearTo,
        public string $description,
        public int $powerPs,
        public int $powerKw,
        public string $engineType,
        public ?string $gearType,
        public ?string $driveType,
        public ?string $brakeSystemType,
        public ?int $numberOfCylinders,
        public ?float $capacityLt,
        public string $type,
        public ?int $vehicleId = null,
    ) {}

    /**
     * Возвращает копию TD-строки с resolved internal vehicle id.
     */
    public function withVehicleId(int $vehicleId): self
    {
        return new self(
            msId: $this->msId,
            modId: $this->modId,
            yearFrom: $this->yearFrom,
            yearTo: $this->yearTo,
            description: $this->description,
            powerPs: $this->powerPs,
            powerKw: $this->powerKw,
            engineType: $this->engineType,
            gearType: $this->gearType,
            driveType: $this->driveType,
            brakeSystemType: $this->brakeSystemType,
            numberOfCylinders: $this->numberOfCylinders,
            capacityLt: $this->capacityLt,
            type: $this->type,
            vehicleId: $vehicleId,
        );
    }

    /**
     * Возвращает payload TD-строки для сборки ModificationData.
     *
     * @return array<string, string|int|float|array<int, string>|null>
     */
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
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => ['year_from', 'year_to'],
        ];
    }
}
