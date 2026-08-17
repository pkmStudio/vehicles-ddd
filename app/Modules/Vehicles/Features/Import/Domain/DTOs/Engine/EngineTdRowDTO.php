<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

final readonly class EngineTdRowDTO
{
    /**
     * Фиксирует строку command-импорта двигателя из TecDoc cascade.
     */
    public function __construct(
        public int $engId,
        public string $codeEngine,
        public int $powerKwStart,
        public int $powerPsStart,
        public string $fuelType,
        public ?int $powerKwUpto,
        public ?int $powerPsUpto,
        public ?float $engineCapacity,
        public ?float $cylinderDiameter,
        public ?int $cylinderCount,
        public ?int $numberOfValves,
    ) {}

    /**
     * Возвращает payload TD-строки для сборки EngineData.
     *
     * @return array<string, string|int|float|array<int, string>|null>
     */
    public function toArray(): array
    {
        return [
            'eng_id' => $this->engId,
            'code_engine' => $this->codeEngine,
            'power_kw_start' => $this->powerKwStart,
            'power_ps_start' => $this->powerPsStart,
            'fuel_type' => $this->fuelType,
            'power_kw_upto' => $this->powerKwUpto,
            'power_ps_upto' => $this->powerPsUpto,
            'engine_capacity' => $this->engineCapacity,
            'cylinder_diameter' => $this->cylinderDiameter,
            'cylinder_count' => $this->cylinderCount,
            'number_of_valves' => $this->numberOfValves,
            'provider' => ProviderEnum::TD->value,
            'allow_change_fields' => [],
        ];
    }
}
