<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

final readonly class EngineSheetRowDTO
{
    /**
     * Фиксирует нормализованную строку Excel-листа двигателей.
     */
    public function __construct(
        public ?int $engId,
        public ?string $codeEngine,
        public ?int $powerKwStart,
        public ?int $powerKwUpto,
        public ?int $powerPsStart,
        public ?int $powerPsUpto,
        public ?string $engineCapacity,
        public ?float $cylinderDiameter,
        public ?int $cylinderCount,
        public ?int $numberOfValves,
        public ?string $fuelType,
        public ProviderEnum $provider,
        public array $allowChangeFields,
        public string $operationId,
        public bool $generateNegativeEngIdWhenMissing = false,
    ) {}

    /**
     * Возвращает копию строки с resolved eng_id.
     */
    public function withEngId(int $engId): self
    {
        return new self(
            engId: $engId,
            codeEngine: $this->codeEngine,
            powerKwStart: $this->powerKwStart,
            powerKwUpto: $this->powerKwUpto,
            powerPsStart: $this->powerPsStart,
            powerPsUpto: $this->powerPsUpto,
            engineCapacity: $this->engineCapacity,
            cylinderDiameter: $this->cylinderDiameter,
            cylinderCount: $this->cylinderCount,
            numberOfValves: $this->numberOfValves,
            fuelType: $this->fuelType,
            provider: $this->provider,
            allowChangeFields: $this->allowChangeFields,
            operationId: $this->operationId,
            generateNegativeEngIdWhenMissing: $this->generateNegativeEngIdWhenMissing,
        );
    }

    /**
     * Возвращает payload строки для сборки EngineData.
     *
     * @return array<string, string|int|float|array<int, string>|null>
     */
    public function toArray(): array
    {
        return [
            'eng_id' => $this->engId,
            'code_engine' => $this->codeEngine,
            'power_kw_start' => $this->powerKwStart,
            'power_kw_upto' => $this->powerKwUpto,
            'power_ps_start' => $this->powerPsStart,
            'power_ps_upto' => $this->powerPsUpto,
            'engine_capacity' => $this->engineCapacity,
            'cylinder_diameter' => $this->cylinderDiameter,
            'cylinder_count' => $this->cylinderCount,
            'number_of_valves' => $this->numberOfValves,
            'fuel_type' => $this->fuelType,
            'provider' => $this->provider->value,
            'allow_change_fields' => $this->allowChangeFields,
        ];
    }
}
