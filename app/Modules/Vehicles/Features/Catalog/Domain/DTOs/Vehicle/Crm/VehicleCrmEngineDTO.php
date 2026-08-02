<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

/**
 * Сценарный снимок двигателя внутри CRM detail projection автомобиля.
 */
final readonly class VehicleCrmEngineDTO
{
    /**
     * Хранит поля двигателя, показываемые во вложенном CRM detail ответе.
     */
    public function __construct(
        public int $id,
        public int $engId,
        public ?string $codeEngine = null,
        public ?string $engineCapacity = null,
        public ?int $cylinderCount = null,
        public ?float $cylinderDiameter = null,
        public ?int $engPowerKwStart = null,
        public ?int $engPowerKwUpto = null,
        public ?int $engPowerPsStart = null,
        public ?int $engPowerPsUpto = null,
        public ?int $engNumberOfValves = null,
        public ?string $engFuelType = null,
        public ?int $groupId = null,
    ) {}

    /**
     * Возвращает публичный payload двигателя для CRM detail ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'eng_id' => $this->engId,
            'code_engine' => $this->codeEngine,
            'engine_capacity' => $this->engineCapacity,
            'cylinder_count' => $this->cylinderCount,
            'cylinder_diameter' => $this->cylinderDiameter,
            'eng_power_kw_start' => $this->engPowerKwStart,
            'eng_power_kw_upto' => $this->engPowerKwUpto,
            'eng_power_ps_start' => $this->engPowerPsStart,
            'eng_power_ps_upto' => $this->engPowerPsUpto,
            'eng_number_of_valves' => $this->engNumberOfValves,
            'eng_fuel_type' => $this->engFuelType,
            'group_id' => $this->groupId,
        ];
    }
}
