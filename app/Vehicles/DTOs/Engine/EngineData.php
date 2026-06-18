<?php

declare(strict_types=1);

namespace App\Vehicles\DTOs\Engine;

final readonly class EngineData
{
    public function __construct(
        public int $engId,
        public ?string $codeEngine = null,
        public ?int $engPowerKwStart = null,
        public ?int $engPowerKwUpto = null,
        public ?int $engPowerPsStart = null,
        public ?int $engPowerPsUpto = null,
        public ?string $engineCapacity = null,
        public ?float $cylinderDiameter = null,
        public ?int $cylinderCount = null,
        public ?int $engNumberOfValves = null,
        public ?string $engFuelType = null,
    ) {}

    public function toArray(): array
    {
        return [
            'eng_id' => $this->engId,
            'code_engine' => $this->codeEngine,
            'eng_power_kw_start' => $this->engPowerKwStart,
            'eng_power_kw_upto' => $this->engPowerKwUpto,
            'eng_power_ps_start' => $this->engPowerPsStart,
            'eng_power_ps_upto' => $this->engPowerPsUpto,
            'engine_capacity' => $this->engineCapacity,
            'cylinder_diameter' => $this->cylinderDiameter,
            'cylinder_count' => $this->cylinderCount,
            'eng_number_of_valves' => $this->engNumberOfValves,
            'eng_fuel_type' => $this->engFuelType,
        ];
    }
}
