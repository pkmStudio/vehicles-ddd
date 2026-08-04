<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;

/**
 * Передает параметры сценария или результат мутации двигателей.
 */
final readonly class EngineMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных двигателей.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateEngineRequestDTO|UpdateEngineRequestDTO|DeleteEngineRequestDTO $request,
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
    private static function createRequest(array $payload): CreateEngineRequestDTO
    {
        return self::upsertRequest($payload, CreateEngineRequestDTO::class);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function updateRequest(array $payload): UpdateEngineRequestDTO
    {
        return self::upsertRequest($payload, UpdateEngineRequestDTO::class);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function deleteRequest(array $payload): DeleteEngineRequestDTO
    {
        return new DeleteEngineRequestDTO(
            userId: (int) $payload['user_id'],
            operationId: (string) $payload['operation_id'],
            engId: (int) $payload['engine']['eng_id'],
        );
    }

    /**
     * @template T of CreateEngineRequestDTO|UpdateEngineRequestDTO
     *
     * @param  array<string, mixed>  $payload
     * @param  class-string<T>  $class
     * @return T
     */
    private static function upsertRequest(array $payload, string $class): CreateEngineRequestDTO|UpdateEngineRequestDTO
    {
        $engine = $payload['engine'];

        return new $class(
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
}
