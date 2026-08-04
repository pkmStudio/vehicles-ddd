<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
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
            modId: (int) $modification['mod_id'],
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
        );
    }
}
