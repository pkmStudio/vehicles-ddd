<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;

/**
 * Публичная REST-проекция модификации ТС для каталога.
 */
final readonly class CatalogModificationDTO
{
    /**
     * Хранит публичные поля модификации для ответа каталога.
     */
    public function __construct(
        public int $id,
        public int $modId,
        public int $vehicleId,
        public int $msId,
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
    ) {}

    /**
     * Собирает публичную проекцию модификации из Data-снимка.
     */
    public static function fromData(ModificationData $modification): self
    {
        return new self(
            id: (int) $modification->id,
            modId: $modification->modId,
            vehicleId: $modification->vehicleId,
            msId: $modification->msId,
            yearFrom: $modification->yearFrom,
            yearTo: $modification->yearTo,
            description: $modification->description,
            powerPs: $modification->powerPs,
            powerKw: $modification->powerKw,
            engineType: $modification->engineType?->value,
            gearType: $modification->gearType?->value,
            driveType: $modification->driveType?->value,
            brakeSystemType: $modification->brakeSystemType?->value,
            numberOfCylinders: $modification->numberOfCylinders,
            capacityLt: $modification->capacityLt,
        );
    }

    /** @return array<string, float|int|string|null> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'mod_id' => $this->modId,
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
