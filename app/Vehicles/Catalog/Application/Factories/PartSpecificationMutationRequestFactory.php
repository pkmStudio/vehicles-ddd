<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Application\Factories;

use App\Templates\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Catalog\Domain\Contracts\Factories\PartSpecificationMutationRequestFactoryInterface;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\CreatePartSpecificationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\DeletePartSpecificationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationMutationRequestDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerEngineDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerVehicleDTO;
use App\Vehicles\Catalog\Domain\DTOs\PartSpecification\UpdatePartSpecificationRequestDTO;
use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Собирает DTO запроса мутации спецификаций деталей из валидированного payload.
 */
final readonly class PartSpecificationMutationRequestFactory implements PartSpecificationMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации спецификаций деталей из payload.
     *
     * Шаги:
     * 1) Прочитать тип операции из payload.
     * 2) Собрать конкретный DTO запроса операции.
     * 3) Вернуть общий DTO мутации с операцией и request.
     */
    public function make(array $payload): PartSpecificationMutationRequestDTO
    {
        $operation = CatalogMutationOperationEnum::from((string) $payload['operation']);

        return new PartSpecificationMutationRequestDTO(
            operation: $operation,
            request: match ($operation) {
                CatalogMutationOperationEnum::Create => $this->createRequest($payload),
                CatalogMutationOperationEnum::Update => $this->updateRequest($payload),
                CatalogMutationOperationEnum::Delete => $this->deleteRequest($payload),
            },
        );
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function createRequest(array $payload): CreatePartSpecificationRequestDTO
    {
        $specification = $payload['part_specification'];

        return new CreatePartSpecificationRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $specification['id'],
            owner: $this->owner($specification),
            template: DetailTemplateEnum::from((string) $specification['template']),
            details: $specification['details'],
            featureValueId: isset($specification['feature_value_id']) ? (int) $specification['feature_value_id'] : null,
            name: isset($specification['name']) ? (string) $specification['name'] : null,
            text: isset($specification['text']) ? (string) $specification['text'] : null,
        );
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function updateRequest(array $payload): UpdatePartSpecificationRequestDTO
    {
        $specification = $payload['part_specification'];

        return new UpdatePartSpecificationRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $specification['id'],
            owner: $this->owner($specification),
            template: DetailTemplateEnum::from((string) $specification['template']),
            details: $specification['details'],
            featureValueId: isset($specification['feature_value_id']) ? (int) $specification['feature_value_id'] : null,
            name: isset($specification['name']) ? (string) $specification['name'] : null,
            text: isset($specification['text']) ? (string) $specification['text'] : null,
        );
    }

    /**
     * Собирает DTO конкретной операции спецификаций деталей из общего DTO или payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function deleteRequest(array $payload): DeletePartSpecificationRequestDTO
    {
        return new DeletePartSpecificationRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: (int) $payload['part_specification']['id'],
        );
    }

    /**
     * Собирает владельца спеки из payload.
     *
     * @param  array<string, mixed>  $specification
     */
    private function owner(array $specification): PartSpecificationOwnerDTO
    {
        $owner = $specification['owner'];
        $type = PartableTypeEnum::from((string) $owner['type']);

        return new PartSpecificationOwnerDTO(
            type: $type,
            externalId: (int) $owner['external_id'],
            vehicle: $type === PartableTypeEnum::VEHICLE && isset($owner['vehicle'])
                ? $this->vehicleOwner($owner['vehicle'])
                : null,
            engine: $type === PartableTypeEnum::ENGINE && isset($owner['engine'])
                ? $this->engineOwner($owner['engine'])
                : null,
        );
    }

    /**
     * Собирает payload автомобиля-владельца спеки.
     *
     * @param  array<string, mixed>  $vehicle
     */
    private function vehicleOwner(array $vehicle): PartSpecificationOwnerVehicleDTO
    {
        return new PartSpecificationOwnerVehicleDTO(
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
     * Собирает payload двигателя-владельца спеки.
     *
     * @param  array<string, mixed>  $engine
     */
    private function engineOwner(array $engine): PartSpecificationOwnerEngineDTO
    {
        return new PartSpecificationOwnerEngineDTO(
            codeEngine: isset($engine['code_engine']) ? (string) $engine['code_engine'] : null,
            engPowerKwStart: isset($engine['eng_power_kw_start']) ? (int) $engine['eng_power_kw_start'] : null,
            engPowerKwUpto: isset($engine['eng_power_kw_upto']) ? (int) $engine['eng_power_kw_upto'] : null,
            engPowerPsStart: isset($engine['eng_power_ps_start']) ? (int) $engine['eng_power_ps_start'] : null,
            engPowerPsUpto: isset($engine['eng_power_ps_upto']) ? (int) $engine['eng_power_ps_upto'] : null,
            engineCapacity: isset($engine['engine_capacity']) ? (string) $engine['engine_capacity'] : null,
            cylinderDiameter: isset($engine['cylinder_diameter']) ? (float) $engine['cylinder_diameter'] : null,
            cylinderCount: isset($engine['cylinder_count']) ? (int) $engine['cylinder_count'] : null,
            engNumberOfValves: isset($engine['eng_number_of_valves']) ? (int) $engine['eng_number_of_valves'] : null,
            engFuelType: isset($engine['eng_fuel_type']) ? EngineFuelTypeEnum::from((string) $engine['eng_fuel_type']) : null,
            groupId: isset($engine['group_id']) ? (int) $engine['group_id'] : null,
        );
    }
}
