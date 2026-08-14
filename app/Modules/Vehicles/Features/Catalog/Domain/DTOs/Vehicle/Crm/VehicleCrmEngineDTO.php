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
        public string $codeEngine,
        public int $powerKwStart,
        public int $powerPsStart,
        public string $fuelType,
        public string $provider,
        public array $allowChangeFields,
        public ?string $engineCapacity = null,
        public ?int $cylinderCount = null,
        public ?float $cylinderDiameter = null,
        public ?int $powerKwUpto = null,
        public ?int $powerPsUpto = null,
        public ?int $numberOfValves = null,
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
            codeEngine: (string) $data['code_engine'],
            powerKwStart: (int) $data['power_kw_start'],
            powerPsStart: (int) $data['power_ps_start'],
            fuelType: (string) $data['fuel_type'],
            provider: (string) $data['provider'],
            allowChangeFields: $data['allow_change_fields'],
            engineCapacity: isset($data['engine_capacity']) ? (string) $data['engine_capacity'] : null,
            cylinderCount: isset($data['cylinder_count']) ? (int) $data['cylinder_count'] : null,
            cylinderDiameter: isset($data['cylinder_diameter']) ? (float) $data['cylinder_diameter'] : null,
            powerKwUpto: isset($data['power_kw_upto']) ? (int) $data['power_kw_upto'] : null,
            powerPsUpto: isset($data['power_ps_upto']) ? (int) $data['power_ps_upto'] : null,
            numberOfValves: isset($data['number_of_valves']) ? (int) $data['number_of_valves'] : null,
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
            'power_kw_start' => $this->powerKwStart,
            'power_ps_start' => $this->powerPsStart,
            'fuel_type' => $this->fuelType,
            'engine_capacity' => $this->engineCapacity,
            'cylinder_count' => $this->cylinderCount,
            'cylinder_diameter' => $this->cylinderDiameter,
            'power_kw_upto' => $this->powerKwUpto,
            'power_ps_upto' => $this->powerPsUpto,
            'number_of_valves' => $this->numberOfValves,
            'group_id' => $this->groupId,
            'provider' => $this->provider,
            'allow_change_fields' => $this->allowChangeFields,
        ];
    }
}
