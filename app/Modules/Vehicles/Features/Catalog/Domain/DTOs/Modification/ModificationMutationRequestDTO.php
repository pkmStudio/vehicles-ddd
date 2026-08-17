<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
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
     * @param  array{
     *     user_id: int|string,
     *     operation_id: string,
     *     operation: string,
     *     modification: array{
     *         mod_id?: int|string|null,
     *         ms_id?: int|string,
     *         type: string,
     *         allow_change_fields?: array<int, string>,
     *         provider?: string,
     *         year_from?: int|string,
     *         year_to?: int|string|null,
     *         description?: string,
     *         description_short?: string|null,
     *         power_ps?: int|string,
     *         power_kw?: int|string,
     *         engine_type?: string,
     *         gear_type?: string|null,
     *         drive_type?: string|null,
     *         brake_system_type?: string|null,
     *         number_of_cylinders?: int|string|null,
     *         capacity_lt?: int|float|string|null,
     *         localized_name?: string|null,
     *         engines?: list<array{eng_id: int|string, relation_provider: string}>
     *     }
     * } $payload
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
     * @param  array{
     *     user_id: int|string,
     *     operation_id: string,
     *     operation: string,
     *     modification: array{
     *         mod_id?: int|string|null,
     *         ms_id?: int|string,
     *         type: string,
     *         allow_change_fields?: array<int, string>,
     *         provider?: string,
     *         year_from?: int|string,
     *         year_to?: int|string|null,
     *         description?: string,
     *         description_short?: string|null,
     *         power_ps?: int|string,
     *         power_kw?: int|string,
     *         engine_type?: string,
     *         gear_type?: string|null,
     *         drive_type?: string|null,
     *         brake_system_type?: string|null,
     *         number_of_cylinders?: int|string|null,
     *         capacity_lt?: int|float|string|null,
     *         localized_name?: string|null,
     *         engines?: list<array{eng_id: int|string, relation_provider: string}>
     *     }
     * } $payload
     */
    private static function createRequest(array $payload): CreateModificationRequestDTO
    {
        return self::upsertRequest($payload, CreateModificationRequestDTO::class);
    }

    /**
     * @param  array{
     *     user_id: int|string,
     *     operation_id: string,
     *     operation: string,
     *     modification: array{
     *         mod_id?: int|string|null,
     *         ms_id?: int|string,
     *         type: string,
     *         allow_change_fields?: array<int, string>,
     *         provider?: string,
     *         year_from?: int|string,
     *         year_to?: int|string|null,
     *         description?: string,
     *         description_short?: string|null,
     *         power_ps?: int|string,
     *         power_kw?: int|string,
     *         engine_type?: string,
     *         gear_type?: string|null,
     *         drive_type?: string|null,
     *         brake_system_type?: string|null,
     *         number_of_cylinders?: int|string|null,
     *         capacity_lt?: int|float|string|null,
     *         localized_name?: string|null,
     *         engines?: list<array{eng_id: int|string, relation_provider: string}>
     *     }
     * } $payload
     */
    private static function updateRequest(array $payload): UpdateModificationRequestDTO
    {
        return self::upsertRequest($payload, UpdateModificationRequestDTO::class);
    }

    /**
     * @param  array{
     *     user_id: int|string,
     *     operation_id: string,
     *     operation: string,
     *     modification: array{mod_id: int|string, type: string}
     * } $payload
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
     * @param  array{
     *     user_id: int|string,
     *     operation_id: string,
     *     operation: string,
     *     modification: array{
     *         mod_id?: int|string|null,
     *         ms_id?: int|string,
     *         type: string,
     *         allow_change_fields: array<int, string>,
     *         provider: string,
     *         year_from: int|string,
     *         year_to?: int|string|null,
     *         description: string,
     *         description_short?: string|null,
     *         power_ps: int|string,
     *         power_kw: int|string,
     *         engine_type: string,
     *         gear_type?: string|null,
     *         drive_type?: string|null,
     *         brake_system_type?: string|null,
     *         number_of_cylinders?: int|string|null,
     *         capacity_lt?: int|float|string|null,
     *         localized_name?: string|null,
     *         engines?: list<array{eng_id: int|string, relation_provider: string}>
     *     }
     * } $payload
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
            allowChangeFields: array_values($modification['allow_change_fields']),
            provider: ProviderEnum::from((string) $modification['provider']),
            yearFrom: (int) $modification['year_from'],
            yearTo: isset($modification['year_to']) ? (int) $modification['year_to'] : null,
            description: (string) $modification['description'],
            descriptionShort: isset($modification['description_short']) ? (string) $modification['description_short'] : null,
            powerPs: (int) $modification['power_ps'],
            powerKw: (int) $modification['power_kw'],
            engineType: EngineTypeEnum::from((string) $modification['engine_type']),
            gearType: isset($modification['gear_type']) ? GearTypeEnum::from((string) $modification['gear_type']) : null,
            driveType: isset($modification['drive_type']) ? DriveTypeEnum::from((string) $modification['drive_type']) : null,
            brakeSystemType: isset($modification['brake_system_type']) ? BrakeSystemTypeEnum::from((string) $modification['brake_system_type']) : null,
            numberOfCylinders: isset($modification['number_of_cylinders']) ? (int) $modification['number_of_cylinders'] : null,
            capacityLt: isset($modification['capacity_lt']) ? (float) $modification['capacity_lt'] : null,
            localizedName: isset($modification['localized_name']) ? (string) $modification['localized_name'] : null,
            engines: self::engines($modification['engines'] ?? []),
            syncEngines: array_key_exists('engines', $modification),
        );
    }

    /**
     * @param  list<array{eng_id: int|string, relation_provider: string}>  $engines
     * @return list<ModificationEngineRequestDTO>
     */
    private static function engines(array $engines): array
    {
        return array_values(array_map(
            static fn (array $engine): ModificationEngineRequestDTO => new ModificationEngineRequestDTO(
                engId: (int) $engine['eng_id'],
                relationProvider: ProviderEnum::from((string) $engine['relation_provider']),
            ),
            $engines,
        ));
    }
}
