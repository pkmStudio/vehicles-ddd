<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs;

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
        public ?string $codeEngine = null,
        public ?int $powerKwStart = null,
        public ?int $powerKwUpto = null,
        public ?int $powerPsStart = null,
        public ?int $powerPsUpto = null,
        public ?string $engineCapacity = null,
        public ?float $cylinderDiameter = null,
        public ?int $cylinderCount = null,
        public ?int $numberOfValves = null,
        public ?EngineFuelTypeEnum $fuelType = null,
        public ?int $groupId = null,
        public array $allowChangeFields = [],
        public ?int $id = null,
    ) {}

    /**
     * Собирает DTO из snake_case массива локального Data-снимка.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            engId: (int) $payload['eng_id'],
            provider: $payload['provider'] instanceof ProviderEnum
                ? $payload['provider']
                : ProviderEnum::from((string) $payload['provider']),
            codeEngine: isset($payload['code_engine']) ? (string) $payload['code_engine'] : null,
            powerKwStart: isset($payload['power_kw_start']) ? (int) $payload['power_kw_start'] : null,
            powerKwUpto: isset($payload['power_kw_upto']) ? (int) $payload['power_kw_upto'] : null,
            powerPsStart: isset($payload['power_ps_start']) ? (int) $payload['power_ps_start'] : null,
            powerPsUpto: isset($payload['power_ps_upto']) ? (int) $payload['power_ps_upto'] : null,
            engineCapacity: isset($payload['engine_capacity']) ? (string) $payload['engine_capacity'] : null,
            cylinderDiameter: isset($payload['cylinder_diameter']) ? (float) $payload['cylinder_diameter'] : null,
            cylinderCount: isset($payload['cylinder_count']) ? (int) $payload['cylinder_count'] : null,
            numberOfValves: isset($payload['number_of_valves']) ? (int) $payload['number_of_valves'] : null,
            fuelType: self::nullableFuelType($payload['fuel_type'] ?? null),
            groupId: isset($payload['group_id']) ? (int) $payload['group_id'] : null,
            allowChangeFields: array_values($payload['allow_change_fields'] ?? []),
            id: isset($payload['id']) ? (int) $payload['id'] : null,
        );
    }

    /**
     * Возвращает snake_case массив для передачи в feature-local Spatie Data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eng_id' => $this->engId,
            'provider' => $this->provider->value,
            'code_engine' => $this->codeEngine,
            'power_kw_start' => $this->powerKwStart,
            'power_kw_upto' => $this->powerKwUpto,
            'power_ps_start' => $this->powerPsStart,
            'power_ps_upto' => $this->powerPsUpto,
            'engine_capacity' => $this->engineCapacity,
            'cylinder_diameter' => $this->cylinderDiameter,
            'cylinder_count' => $this->cylinderCount,
            'number_of_valves' => $this->numberOfValves,
            'fuel_type' => $this->fuelType?->value,
            'group_id' => $this->groupId,
            'allow_change_fields' => $this->allowChangeFields,
            'id' => $this->id,
        ];
    }

    /**
     * Возвращает nullable fuel type enum из enum/string значения.
     */
    private static function nullableFuelType(mixed $value): ?EngineFuelTypeEnum
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof EngineFuelTypeEnum) {
            return $value;
        }

        return EngineFuelTypeEnum::from((string) $value);
    }
}
