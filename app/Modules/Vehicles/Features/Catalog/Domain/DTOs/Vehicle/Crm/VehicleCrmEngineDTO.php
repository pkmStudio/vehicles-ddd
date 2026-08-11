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
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            engId: (int) $data['eng_id'],
            codeEngine: isset($data['code_engine']) ? (string) $data['code_engine'] : null,
            engineCapacity: isset($data['engine_capacity']) ? (string) $data['engine_capacity'] : null,
            cylinderCount: isset($data['cylinder_count']) ? (int) $data['cylinder_count'] : null,
            cylinderDiameter: isset($data['cylinder_diameter']) ? (float) $data['cylinder_diameter'] : null,
            engPowerKwStart: isset($data['eng_power_kw_start']) ? (int) $data['eng_power_kw_start'] : null,
            engPowerKwUpto: isset($data['eng_power_kw_upto']) ? (int) $data['eng_power_kw_upto'] : null,
            engPowerPsStart: isset($data['eng_power_ps_start']) ? (int) $data['eng_power_ps_start'] : null,
            engPowerPsUpto: isset($data['eng_power_ps_upto']) ? (int) $data['eng_power_ps_upto'] : null,
            engNumberOfValves: isset($data['eng_number_of_valves']) ? (int) $data['eng_number_of_valves'] : null,
            engFuelType: isset($data['eng_fuel_type']) ? (string) $data['eng_fuel_type'] : null,
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
        );
    }

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
