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
        public ?int $powerKwStart = null,
        public ?int $powerKwUpto = null,
        public ?int $powerPsStart = null,
        public ?int $powerPsUpto = null,
        public ?int $numberOfValves = null,
        public ?string $fuelType = null,
        public ?int $groupId = null,
        public string $provider = 'TD',
        public array $allowChangeFields = [],
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
            powerKwStart: isset($data['power_kw_start']) ? (int) $data['power_kw_start'] : null,
            powerKwUpto: isset($data['power_kw_upto']) ? (int) $data['power_kw_upto'] : null,
            powerPsStart: isset($data['power_ps_start']) ? (int) $data['power_ps_start'] : null,
            powerPsUpto: isset($data['power_ps_upto']) ? (int) $data['power_ps_upto'] : null,
            numberOfValves: isset($data['number_of_valves']) ? (int) $data['number_of_valves'] : null,
            fuelType: isset($data['fuel_type']) ? (string) $data['fuel_type'] : null,
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
            provider: isset($data['provider']) ? (string) $data['provider'] : 'TD',
            allowChangeFields: self::stringList($data['allow_change_fields'] ?? []),
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
            'power_kw_start' => $this->powerKwStart,
            'power_kw_upto' => $this->powerKwUpto,
            'power_ps_start' => $this->powerPsStart,
            'power_ps_upto' => $this->powerPsUpto,
            'number_of_valves' => $this->numberOfValves,
            'fuel_type' => $this->fuelType,
            'group_id' => $this->groupId,
            'provider' => $this->provider,
            'allow_change_fields' => $this->allowChangeFields,
        ];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): ?string => is_scalar($item) ? (string) $item : null,
            $value,
        )));
    }
}
