<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Policy;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Общий снимок двигателя для provider-aware write policy.
 */
final readonly class EngineWritePolicyResultDTO
{
    /**
     * @param  array<int, string>  $allowChangeFields
     */
    public function __construct(
        public int $engId,
        public ProviderEnum $provider,
        public string $codeEngine,
        public int $powerKwStart,
        public int $powerPsStart,
        public EngineFuelTypeEnum $fuelType,
        public array $allowChangeFields,
        public ?int $powerKwUpto = null,
        public ?int $powerPsUpto = null,
        public ?float $engineCapacity = null,
        public ?float $cylinderDiameter = null,
        public ?int $cylinderCount = null,
        public ?int $numberOfValves = null,
        public ?int $groupId = null,
        public ?int $id = null,
    ) {}

    /**
     * Собирает DTO из snake_case массива локального Data-снимка.
     *
     * @param  array{
     *     eng_id: int|string,
     *     provider: string,
     *     code_engine: string,
     *     power_kw_start: int|string,
     *     power_ps_start: int|string,
     *     fuel_type: string,
     *     allow_change_fields: array<int, string>,
     *     power_kw_upto?: int|string|null,
     *     power_ps_upto?: int|string|null,
     *     engine_capacity?: int|float|string|null,
     *     cylinder_diameter?: int|float|string|null,
     *     cylinder_count?: int|string|null,
     *     number_of_valves?: int|string|null,
     *     group_id?: int|string|null,
     *     id?: int|string|null
     * } $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            engId: (int) $payload['eng_id'],
            provider: ProviderEnum::from($payload['provider']),
            codeEngine: (string) $payload['code_engine'],
            powerKwStart: (int) $payload['power_kw_start'],
            powerPsStart: (int) $payload['power_ps_start'],
            fuelType: EngineFuelTypeEnum::from($payload['fuel_type']),
            allowChangeFields: array_values($payload['allow_change_fields']),
            powerKwUpto: isset($payload['power_kw_upto']) ? (int) $payload['power_kw_upto'] : null,
            powerPsUpto: isset($payload['power_ps_upto']) ? (int) $payload['power_ps_upto'] : null,
            engineCapacity: isset($payload['engine_capacity']) ? (float) $payload['engine_capacity'] : null,
            cylinderDiameter: isset($payload['cylinder_diameter']) ? (float) $payload['cylinder_diameter'] : null,
            cylinderCount: isset($payload['cylinder_count']) ? (int) $payload['cylinder_count'] : null,
            numberOfValves: isset($payload['number_of_valves']) ? (int) $payload['number_of_valves'] : null,
            groupId: isset($payload['group_id']) ? (int) $payload['group_id'] : null,
            id: isset($payload['id']) ? (int) $payload['id'] : null,
        );
    }

    /**
     * Возвращает snake_case массив для передачи в feature-local Spatie Data.
     *
     * @return array{
     *     eng_id: int,
     *     provider: string,
     *     code_engine: string,
     *     power_kw_start: int,
     *     power_ps_start: int,
     *     fuel_type: string,
     *     power_kw_upto: int|null,
     *     power_ps_upto: int|null,
     *     engine_capacity: float|null,
     *     cylinder_diameter: float|null,
     *     cylinder_count: int|null,
     *     number_of_valves: int|null,
     *     group_id: int|null,
     *     allow_change_fields: array<int, string>,
     *     id: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'eng_id' => $this->engId,
            'provider' => $this->provider->value,
            'code_engine' => $this->codeEngine,
            'power_kw_start' => $this->powerKwStart,
            'power_ps_start' => $this->powerPsStart,
            'fuel_type' => $this->fuelType->value,
            'power_kw_upto' => $this->powerKwUpto,
            'power_ps_upto' => $this->powerPsUpto,
            'engine_capacity' => $this->engineCapacity,
            'cylinder_diameter' => $this->cylinderDiameter,
            'cylinder_count' => $this->cylinderCount,
            'number_of_valves' => $this->numberOfValves,
            'group_id' => $this->groupId,
            'allow_change_fields' => $this->allowChangeFields,
            'id' => $this->id,
        ];
    }
}
