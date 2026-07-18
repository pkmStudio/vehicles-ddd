<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Factories\EngineMutationRequestFactoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\CreateEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\DeleteEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineMutationRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\UpdateEngineRequestDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;

/**
 * Собирает DTO запроса мутации двигателей из валидированного payload.
 */
final readonly class EngineMutationRequestFactory implements EngineMutationRequestFactoryInterface
{
    /**
     * Собирает DTO мутации двигателей из payload.
     *
     * Шаги:
     * 1) Прочитать тип операции из payload.
     * 2) Собрать конкретный DTO запроса операции.
     * 3) Вернуть общий DTO мутации с операцией и request.
     */
    public function make(array $payload): EngineMutationRequestDTO
    {
        $operation = CatalogMutationOperationEnum::from((string) $payload['operation']);

        return new EngineMutationRequestDTO(
            operation: $operation,
            request: match ($operation) {
                CatalogMutationOperationEnum::Create => $this->createRequest($payload),
                CatalogMutationOperationEnum::Update => $this->updateRequest($payload),
                CatalogMutationOperationEnum::Delete => $this->deleteRequest($payload),
            },
        );
    }

    /**
     * Собирает DTO конкретной операции двигателей из общего DTO или payload.
     */
    private function createRequest(array $payload): CreateEngineRequestDTO
    {
        $engine = $payload['engine'];

        return new CreateEngineRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            engId: (int) $engine['eng_id'],
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

    /**
     * Собирает DTO конкретной операции двигателей из общего DTO или payload.
     */
    private function updateRequest(array $payload): UpdateEngineRequestDTO
    {
        $engine = $payload['engine'];

        return new UpdateEngineRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            engId: (int) $engine['eng_id'],
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

    /**
     * Удаляет запись двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    private function deleteRequest(array $payload): DeleteEngineRequestDTO
    {
        return new DeleteEngineRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            engId: (int) $payload['engine']['eng_id'],
        );
    }
}
