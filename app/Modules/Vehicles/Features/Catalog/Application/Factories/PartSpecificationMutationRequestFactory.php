<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Factories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\PartSpecificationMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\CreatePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\DeletePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerEngineDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\PartSpecificationOwnerVehicleDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification\UpdatePartSpecificationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

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
     * Собирает request DTO для создания part specification из валидированного catalog payload.
     *
     * Шаги:
     * 1) Извлечь вложенный payload спецификации из сообщения.
     * 2) Собрать owner DTO с vehicle/engine контекстом.
     * 3) Привести template и необязательные поля спецификации к локальным типам.
     * 4) Вернуть request DTO create-сценария с user/operation correlation.
     *
     * @param  array<string, mixed>  $payload
     */
    private function createRequest(array $payload): CreatePartSpecificationRequestDTO
    {
        $specification = $payload['part_specification'];

        return new CreatePartSpecificationRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            id: isset($specification['id']) ? (int) $specification['id'] : null,
            owner: $this->owner($specification),
            template: DetailTemplateEnum::from((string) $specification['template']),
            details: $specification['details'],
            featureValueId: isset($specification['feature_value_id']) ? (int) $specification['feature_value_id'] : null,
            name: isset($specification['name']) ? (string) $specification['name'] : null,
            text: isset($specification['text']) ? (string) $specification['text'] : null,
        );
    }

    /**
     * Собирает request DTO для обновления существующей part specification.
     *
     * Шаги:
     * 1) Извлечь вложенный payload спецификации и обязательный id записи.
     * 2) Собрать owner DTO, потому что update может переназначать владельца.
     * 3) Привести template/details и необязательные descriptive-поля к локальным типам.
     * 4) Вернуть request DTO update-сценария с user/operation correlation.
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
     * Собирает request DTO для удаления part specification.
     *
     * Шаги:
     * 1) Взять user/operation correlation из верхнего уровня payload.
     * 2) Взять id удаляемой спецификации из вложенного part_specification payload.
     * 3) Вернуть DTO delete-сценария без данных владельца и details.
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
     * Шаги:
     * 1) Извлечь owner payload и определить partable type.
     * 2) Записать внешний id владельца в общий owner DTO.
     * 3) Для vehicle owner собрать vehicle snapshot, если он передан и тип совпадает.
     * 4) Для engine owner собрать engine snapshot, если он передан и тип совпадает.
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
     * Собирает typed snapshot автомобиля-владельца part specification.
     *
     * Шаги:
     * 1) Привести обязательные поля mfa_id, name, type и type_carcase к локальным типам.
     * 2) Подставить значения provider/steering по умолчанию, если payload их не содержит.
     * 3) Нормализовать optional generation/excel/year/is_allow поля для возможного создания владельца.
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
     * Собирает typed snapshot двигателя-владельца part specification.
     *
     * Шаги:
     * 1) Прочитать optional engine attributes из owner payload.
     * 2) Привести числовые мощность, объем, цилиндры и group id к нужным scalar-типам.
     * 3) Преобразовать fuel_type в enum, если он передан.
     * 4) Вернуть DTO, пригодный для поиска или создания engine owner.
     *
     * @param  array<string, mixed>  $engine
     */
    private function engineOwner(array $engine): PartSpecificationOwnerEngineDTO
    {
        return new PartSpecificationOwnerEngineDTO(
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
        );
    }
}
