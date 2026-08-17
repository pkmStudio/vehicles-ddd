<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Policy;

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
     * @param  array{
     *     mod_id: int|string,
     *     type: string,
     *     vehicle_id: int|string,
     *     ms_id: int|string,
     *     provider: string,
     *     year_from: int|string,
     *     description: string,
     *     power_ps: int|string,
     *     power_kw: int|string,
     *     engine_type: string,
     *     allow_change_fields: array<int, string>,
     *     year_to?: int|string|null,
     *     description_short?: string|null,
     *     localized_name?: string|null,
     *     gear_type?: string|null,
     *     drive_type?: string|null,
     *     brake_system_type?: string|null,
     *     number_of_cylinders?: int|string|null,
     *     capacity_lt?: int|float|string|null,
     *     id?: int|string|null
     * } $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            modId: (int) $payload['mod_id'],
            type: VehicleTypeEnum::from($payload['type']),
            vehicleId: (int) $payload['vehicle_id'],
            msId: (int) $payload['ms_id'],
            provider: ProviderEnum::from($payload['provider']),
            yearFrom: (int) $payload['year_from'],
            description: (string) $payload['description'],
            powerPs: (int) $payload['power_ps'],
            powerKw: (int) $payload['power_kw'],
            engineType: EngineTypeEnum::from($payload['engine_type']),
            allowChangeFields: array_values($payload['allow_change_fields']),
            yearTo: isset($payload['year_to']) ? (int) $payload['year_to'] : null,
            descriptionShort: isset($payload['description_short']) ? (string) $payload['description_short'] : null,
            localizedName: isset($payload['localized_name']) ? (string) $payload['localized_name'] : null,
            gearType: isset($payload['gear_type']) ? GearTypeEnum::from($payload['gear_type']) : null,
            driveType: isset($payload['drive_type']) ? DriveTypeEnum::from($payload['drive_type']) : null,
            brakeSystemType: isset($payload['brake_system_type']) ? BrakeSystemTypeEnum::from($payload['brake_system_type']) : null,
            numberOfCylinders: isset($payload['number_of_cylinders']) ? (int) $payload['number_of_cylinders'] : null,
            capacityLt: isset($payload['capacity_lt']) ? (float) $payload['capacity_lt'] : null,
            id: isset($payload['id']) ? (int) $payload['id'] : null,
        );
    }

    /**
     * Возвращает snake_case массив для передачи в feature-local Spatie Data.
     *
     * @return array{
     *     mod_id: int,
     *     type: string,
     *     vehicle_id: int,
     *     ms_id: int,
     *     provider: string,
     *     year_from: int,
     *     year_to: int|null,
     *     description: string,
     *     description_short: string|null,
     *     localized_name: string|null,
     *     power_ps: int,
     *     power_kw: int,
     *     engine_type: string,
     *     gear_type: string|null,
     *     drive_type: string|null,
     *     brake_system_type: string|null,
     *     number_of_cylinders: int|null,
     *     capacity_lt: float|null,
     *     allow_change_fields: array<int, string>,
     *     id: int|null
     * }
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
}
