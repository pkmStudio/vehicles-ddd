<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Сценарный снимок двигателя внутри CRM detail projection автомобиля.
 */
final readonly class VehicleCrmEngineDTO implements HttpArraySerializableInterface
{
    /**
     * Хранит поля двигателя, показываемые во вложенном CRM detail ответе.
     *
     * @param  array<int, string>  $allowChangeFields
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
        public ?string $relationProvider = null,
        public ?float $engineCapacity = null,
        public ?int $cylinderCount = null,
        public ?float $cylinderDiameter = null,
        public ?int $powerKwUpto = null,
        public ?int $powerPsUpto = null,
        public ?int $numberOfValves = null,
        public ?int $groupId = null,
    ) {}

    /**
     * @param  array{
     *     id: int|string,
     *     eng_id: int|string,
     *     code_engine: string,
     *     power_kw_start: int|string,
     *     power_ps_start: int|string,
     *     fuel_type: string,
     *     provider: string,
     *     allow_change_fields: array<int, string>,
     *     relation_provider?: string|null,
     *     engine_capacity?: int|float|string|null,
     *     cylinder_count?: int|string|null,
     *     cylinder_diameter?: int|float|string|null,
     *     power_kw_upto?: int|string|null,
     *     power_ps_upto?: int|string|null,
     *     number_of_valves?: int|string|null,
     *     group_id?: int|string|null
     * } $data
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
            relationProvider: isset($data['relation_provider']) ? (string) $data['relation_provider'] : null,
            engineCapacity: isset($data['engine_capacity']) ? (float) $data['engine_capacity'] : null,
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
     * @return array{
     *     id: int,
     *     eng_id: int,
     *     code_engine: string,
     *     power_kw_start: int,
     *     power_ps_start: int,
     *     fuel_type: string,
     *     engine_capacity: float|null,
     *     cylinder_count: int|null,
     *     cylinder_diameter: float|null,
     *     power_kw_upto: int|null,
     *     power_ps_upto: int|null,
     *     number_of_valves: int|null,
     *     group_id: int|null,
     *     provider: string,
     *     relation_provider: string|null,
     *     allow_change_fields: array<int, string>
     * }
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
            'relation_provider' => $this->relationProvider,
            'allow_change_fields' => $this->allowChangeFields,
        ];
    }
}
