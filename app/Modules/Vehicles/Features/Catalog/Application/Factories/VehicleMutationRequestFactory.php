<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\VehicleMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\UpdateVehicleRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Собирает DTO запроса мутации автомобилей из валидированного payload.
 */
final readonly class VehicleMutationRequestFactory implements VehicleMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации автомобилей из payload.
     *
     * Шаги:
     * 1) Прочитать тип операции из payload.
     * 2) Собрать конкретный DTO запроса операции.
     * 3) Вернуть общий DTO мутации с операцией и request.
     */
    public function make(array $payload): VehicleMutationRequestDTO
    {
        $operation = CatalogMutationOperationEnum::from((string) $payload['operation']);

        return new VehicleMutationRequestDTO(
            operation: $operation,
            request: match ($operation) {
                CatalogMutationOperationEnum::Create => $this->createRequest($payload),
                CatalogMutationOperationEnum::Update => $this->updateRequest($payload),
                CatalogMutationOperationEnum::Delete => $this->deleteRequest($payload),
            },
        );
    }

    /**
     * Собирает DTO создания автомобиля из валидированного payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function createRequest(array $payload): CreateVehicleRequestDTO
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
            generation: isset($vehicle['generation']) ? (string) $vehicle['generation'] : null,
            generationShort: isset($vehicle['generation_short']) ? (string) $vehicle['generation_short'] : null,
            localizedName: isset($vehicle['localized_name']) ? (string) $vehicle['localized_name'] : null,
            excelTableId: isset($vehicle['excel_table_id']) ? (string) $vehicle['excel_table_id'] : null,
            generationYearFrom: isset($vehicle['generation_year_from']) ? (int) $vehicle['generation_year_from'] : null,
            generationYearTo: isset($vehicle['generation_year_to']) ? (int) $vehicle['generation_year_to'] : null,
            isAllow: (bool) ($vehicle['is_allow'] ?? false),
        );
    }

    /**
     * Собирает DTO обновления автомобиля из валидированного payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function updateRequest(array $payload): UpdateVehicleRequestDTO
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
            generation: isset($vehicle['generation']) ? (string) $vehicle['generation'] : null,
            generationShort: isset($vehicle['generation_short']) ? (string) $vehicle['generation_short'] : null,
            localizedName: isset($vehicle['localized_name']) ? (string) $vehicle['localized_name'] : null,
            excelTableId: isset($vehicle['excel_table_id']) ? (string) $vehicle['excel_table_id'] : null,
            generationYearFrom: isset($vehicle['generation_year_from']) ? (int) $vehicle['generation_year_from'] : null,
            generationYearTo: isset($vehicle['generation_year_to']) ? (int) $vehicle['generation_year_to'] : null,
            isAllow: (bool) ($vehicle['is_allow'] ?? false),
        );
    }

    /**
     * Собирает DTO удаления автомобиля из валидированного payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function deleteRequest(array $payload): DeleteVehicleRequestDTO
    {
        return new DeleteVehicleRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            msId: (int) $payload['vehicle']['ms_id'],
        );
    }
}
