<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\Factories;

use App\Vehicles\Catalog\Domain\Contracts\Factories\VehicleMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\UpdateVehicleRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\VehicleMutationOperationEnum;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

final readonly class VehicleMutationRequestFactory implements VehicleMutationRequestFactoryInterface
{
    public function make(array $payload): VehicleMutationRequestDTO
    {
        $operation = VehicleMutationOperationEnum::from((string) $payload['operation']);

        return new VehicleMutationRequestDTO(
            operation: $operation,
            request: match ($operation) {
                VehicleMutationOperationEnum::Create => $this->createRequest($payload),
                VehicleMutationOperationEnum::Update => $this->updateRequest($payload),
                VehicleMutationOperationEnum::Delete => $this->deleteRequest($payload),
            },
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createRequest(array $payload): CreateVehicleRequestDTO
    {
        $vehicle = $payload['vehicle'];

        return new CreateVehicleRequestDTO(
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
