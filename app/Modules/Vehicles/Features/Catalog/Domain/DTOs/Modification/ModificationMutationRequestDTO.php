<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Передает параметры сценария или результат мутации модификаций.
 */
final readonly class ModificationMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных модификаций.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateModificationRequestDTO|UpdateModificationRequestDTO|DeleteModificationRequestDTO $request,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $operation = CatalogMutationOperationEnum::from((string) $payload['operation']);

        return new self(
            operation: $operation,
            request: match ($operation) {
                CatalogMutationOperationEnum::Create => self::createRequest($payload),
                CatalogMutationOperationEnum::Update => self::updateRequest($payload),
                CatalogMutationOperationEnum::Delete => self::deleteRequest($payload),
            },
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function createRequest(array $payload): CreateModificationRequestDTO
    {
        return self::upsertRequest($payload, CreateModificationRequestDTO::class);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function updateRequest(array $payload): UpdateModificationRequestDTO
    {
        return self::upsertRequest($payload, UpdateModificationRequestDTO::class);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function deleteRequest(array $payload): DeleteModificationRequestDTO
    {
        return new DeleteModificationRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            modId: (int) $payload['modification']['mod_id'],
            type: VehicleTypeEnum::from((string) $payload['modification']['type']),
        );
    }

    /**
     * @template T of CreateModificationRequestDTO|UpdateModificationRequestDTO
     *
     * @param  array<string, mixed>  $payload
     * @param  class-string<T>  $class
     * @return T
     */
    private static function upsertRequest(array $payload, string $class): CreateModificationRequestDTO|UpdateModificationRequestDTO
    {
        $modification = $payload['modification'];

        return new $class(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            modId: isset($modification['mod_id']) ? (int) $modification['mod_id'] : null,
            msId: (int) $modification['ms_id'],
            type: VehicleTypeEnum::from((string) $modification['type']),
            yearFrom: isset($modification['year_from']) ? (int) $modification['year_from'] : null,
            yearTo: isset($modification['year_to']) ? (int) $modification['year_to'] : null,
            description: isset($modification['description']) ? (string) $modification['description'] : null,
            powerPs: isset($modification['power_ps']) ? (int) $modification['power_ps'] : null,
            powerKw: isset($modification['power_kw']) ? (int) $modification['power_kw'] : null,
            engineType: isset($modification['engine_type']) ? EngineTypeEnum::from((string) $modification['engine_type']) : null,
            gearType: isset($modification['gear_type']) ? GearTypeEnum::from((string) $modification['gear_type']) : null,
            driveType: isset($modification['drive_type']) ? DriveTypeEnum::from((string) $modification['drive_type']) : null,
            brakeSystemType: isset($modification['brake_system_type']) ? BrakeSystemTypeEnum::from((string) $modification['brake_system_type']) : null,
            numberOfCylinders: isset($modification['number_of_cylinders']) ? (int) $modification['number_of_cylinders'] : null,
            capacityLt: isset($modification['capacity_lt']) ? (float) $modification['capacity_lt'] : null,
            localizedName: isset($modification['localized_name']) ? (string) $modification['localized_name'] : null,
            provider: isset($modification['provider']) ? ProviderEnum::from((string) $modification['provider']) : ProviderEnum::OD,
            allowChangeFields: self::stringList($modification['allow_change_fields'] ?? ['year_from', 'year_to']),
            engines: self::engines($modification['engines'] ?? []),
            syncEngines: array_key_exists('engines', $modification),
        );
    }

    /**
     * @return list<ModificationEngineRequestDTO>
     */
    private static function engines(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            static fn (array $engine): ModificationEngineRequestDTO => new ModificationEngineRequestDTO(
                engId: isset($engine['eng_id']) ? (int) $engine['eng_id'] : null,
                codeEngine: isset($engine['code_engine']) ? (string) $engine['code_engine'] : null,
                powerKwStart: isset($engine['power_kw_start']) ? (int) $engine['power_kw_start'] : null,
                powerKwUpto: isset($engine['power_kw_upto']) ? (int) $engine['power_kw_upto'] : null,
                powerPsStart: isset($engine['power_ps_start']) ? (int) $engine['power_ps_start'] : null,
                powerPsUpto: isset($engine['power_ps_upto']) ? (int) $engine['power_ps_upto'] : null,
                engineCapacity: isset($engine['engine_capacity']) ? (string) $engine['engine_capacity'] : null,
                cylinderDiameter: isset($engine['cylinder_diameter']) ? (float) $engine['cylinder_diameter'] : null,
                cylinderCount: isset($engine['cylinder_count']) ? (int) $engine['cylinder_count'] : null,
                numberOfValves: isset($engine['number_of_valves']) ? (int) $engine['number_of_valves'] : null,
                fuelType: isset($engine['fuel_type']) ? EngineFuelTypeEnum::from((string) $engine['fuel_type']) : null,
                groupId: isset($engine['group_id']) ? (int) $engine['group_id'] : null,
                provider: isset($engine['provider']) ? ProviderEnum::from((string) $engine['provider']) : ProviderEnum::OD,
                allowChangeFields: self::stringList($engine['allow_change_fields'] ?? []),
            ),
            array_filter($value, 'is_array'),
        ));
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): ?string => is_scalar($item) ? (string) $item : null,
            $value,
        )));
    }
}
