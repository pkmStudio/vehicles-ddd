<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Передает параметры сценария или результат мутации автомобилей.
 */
final readonly class VehicleMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных автомобилей.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateVehicleRequestDTO|UpdateVehicleRequestDTO|DeleteVehicleRequestDTO $request,
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
    private static function createRequest(array $payload): CreateVehicleRequestDTO
    {
        $vehicle = $payload['vehicle'];

        return new CreateVehicleRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            msId: isset($vehicle['ms_id']) ? (int) $vehicle['ms_id'] : null,
            mfaId: (int) $vehicle['mfa_id'],
            name: (string) $vehicle['name'],
            type: VehicleTypeEnum::from((string) $vehicle['type']),
            typeCarcase: CarcaseTypeEnum::from((string) $vehicle['type_carcase']),
            provider: isset($vehicle['provider']) ? ProviderEnum::from((string) $vehicle['provider']) : ProviderEnum::OD,
            steeringType: isset($vehicle['steering_type'])
                ? SteeringTypeEnum::from((string) $vehicle['steering_type'])
                : SteeringTypeEnum::LEFT,
            parentMsId: isset($vehicle['parent_ms_id']) ? (int) $vehicle['parent_ms_id'] : null,
            generation: (string) $vehicle['generation'],
            generationShort: isset($vehicle['generation_short']) ? (string) $vehicle['generation_short'] : null,
            localizedName: isset($vehicle['localized_name']) ? (string) $vehicle['localized_name'] : null,
            excelTableId: isset($vehicle['excel_table_id']) ? (string) $vehicle['excel_table_id'] : null,
            generationYearFrom: (int) $vehicle['generation_year_from'],
            generationYearTo: isset($vehicle['generation_year_to']) ? (int) $vehicle['generation_year_to'] : null,
            isAllow: (bool) ($vehicle['is_allow'] ?? false),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function updateRequest(array $payload): UpdateVehicleRequestDTO
    {
        $vehicle = $payload['vehicle'];

        return new UpdateVehicleRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            msId: (int) $vehicle['ms_id'],
            mfaId: (int) $vehicle['mfa_id'],
            name: (string) $vehicle['name'],
            type: VehicleTypeEnum::from((string) $vehicle['type']),
            typeCarcase: CarcaseTypeEnum::from((string) $vehicle['type_carcase']),
            provider: isset($vehicle['provider']) ? ProviderEnum::from((string) $vehicle['provider']) : ProviderEnum::OD,
            steeringType: isset($vehicle['steering_type'])
                ? SteeringTypeEnum::from((string) $vehicle['steering_type'])
                : SteeringTypeEnum::LEFT,
            parentMsId: isset($vehicle['parent_ms_id']) ? (int) $vehicle['parent_ms_id'] : null,
            generation: (string) $vehicle['generation'],
            generationShort: isset($vehicle['generation_short']) ? (string) $vehicle['generation_short'] : null,
            localizedName: isset($vehicle['localized_name']) ? (string) $vehicle['localized_name'] : null,
            excelTableId: isset($vehicle['excel_table_id']) ? (string) $vehicle['excel_table_id'] : null,
            generationYearFrom: (int) $vehicle['generation_year_from'],
            generationYearTo: isset($vehicle['generation_year_to']) ? (int) $vehicle['generation_year_to'] : null,
            isAllow: (bool) ($vehicle['is_allow'] ?? false),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function deleteRequest(array $payload): DeleteVehicleRequestDTO
    {
        return new DeleteVehicleRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            msId: (int) $payload['vehicle']['ms_id'],
        );
    }
}
