<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Общий снимок модификации для provider-aware write policy.
 */
final readonly class ModificationWritePolicyResultDTO
{
    /**
     * @param  array<int, string>  $allowChangeFields
     */
    public function __construct(
        public int $modId,
        public VehicleTypeEnum $type,
        public int $vehicleId,
        public int $msId,
        public ProviderEnum $provider,
        public int $yearFrom,
        public string $description,
        public int $powerPs,
        public int $powerKw,
        public EngineTypeEnum $engineType,
        public array $allowChangeFields,
        public ?int $yearTo = null,
        public ?string $descriptionShort = null,
        public ?string $localizedName = null,
        public ?GearTypeEnum $gearType = null,
        public ?DriveTypeEnum $driveType = null,
        public ?BrakeSystemTypeEnum $brakeSystemType = null,
        public ?int $numberOfCylinders = null,
        public ?float $capacityLt = null,
        public ?int $id = null,
    ) {}

    /**
     * Собирает DTO из snake_case массива локального Data-снимка.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            modId: (int) $payload['mod_id'],
            type: $payload['type'] instanceof VehicleTypeEnum
                ? $payload['type']
                : VehicleTypeEnum::from((string) $payload['type']),
            vehicleId: (int) $payload['vehicle_id'],
            msId: (int) $payload['ms_id'],
            provider: $payload['provider'] instanceof ProviderEnum
                ? $payload['provider']
                : ProviderEnum::from((string) $payload['provider']),
            yearFrom: (int) $payload['year_from'],
            yearTo: isset($payload['year_to']) ? (int) $payload['year_to'] : null,
            description: (string) $payload['description'],
            descriptionShort: isset($payload['description_short']) ? (string) $payload['description_short'] : null,
            localizedName: isset($payload['localized_name']) ? (string) $payload['localized_name'] : null,
            powerPs: (int) $payload['power_ps'],
            powerKw: (int) $payload['power_kw'],
            engineType: self::engineType($payload['engine_type']),
            allowChangeFields: array_values($payload['allow_change_fields']),
            gearType: self::nullableGearType($payload['gear_type'] ?? null),
            driveType: self::nullableDriveType($payload['drive_type'] ?? null),
            brakeSystemType: self::nullableBrakeSystemType($payload['brake_system_type'] ?? null),
            numberOfCylinders: isset($payload['number_of_cylinders']) ? (int) $payload['number_of_cylinders'] : null,
            capacityLt: isset($payload['capacity_lt']) ? (float) $payload['capacity_lt'] : null,
            id: isset($payload['id']) ? (int) $payload['id'] : null,
        );
    }

    /**
     * Возвращает snake_case массив для передачи в feature-local Spatie Data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mod_id' => $this->modId,
            'type' => $this->type->value,
            'vehicle_id' => $this->vehicleId,
            'ms_id' => $this->msId,
            'provider' => $this->provider->value,
            'year_from' => $this->yearFrom,
            'year_to' => $this->yearTo,
            'description' => $this->description,
            'description_short' => $this->descriptionShort,
            'localized_name' => $this->localizedName,
            'power_ps' => $this->powerPs,
            'power_kw' => $this->powerKw,
            'engine_type' => $this->engineType->value,
            'gear_type' => $this->gearType?->value,
            'drive_type' => $this->driveType?->value,
            'brake_system_type' => $this->brakeSystemType?->value,
            'number_of_cylinders' => $this->numberOfCylinders,
            'capacity_lt' => $this->capacityLt,
            'allow_change_fields' => $this->allowChangeFields,
            'id' => $this->id,
        ];
    }

    /**
     * Возвращает nullable engine type enum из enum/string значения.
     */
    private static function engineType(mixed $value): EngineTypeEnum
    {
        if ($value instanceof EngineTypeEnum) {
            return $value;
        }

        return EngineTypeEnum::from((string) $value);
    }

    /**
     * Возвращает nullable gear type enum из enum/string значения.
     */
    private static function nullableGearType(mixed $value): ?GearTypeEnum
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof GearTypeEnum) {
            return $value;
        }

        return GearTypeEnum::from((string) $value);
    }

    /**
     * Возвращает nullable drive type enum из enum/string значения.
     */
    private static function nullableDriveType(mixed $value): ?DriveTypeEnum
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DriveTypeEnum) {
            return $value;
        }

        return DriveTypeEnum::from((string) $value);
    }

    /**
     * Возвращает nullable brake system type enum из enum/string значения.
     */
    private static function nullableBrakeSystemType(mixed $value): ?BrakeSystemTypeEnum
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BrakeSystemTypeEnum) {
            return $value;
        }

        return BrakeSystemTypeEnum::from((string) $value);
    }
}
