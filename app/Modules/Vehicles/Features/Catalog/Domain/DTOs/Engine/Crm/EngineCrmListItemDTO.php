<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm;

/**
 * Read-only снимок двигателя для CRM каталога.
 */
final readonly class EngineCrmListItemDTO
{
    /**
     * @param  list<string>  $allowChangeFields
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
        public int $modificationsCount = 0,
    ) {}

    /**
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
            'modifications_count' => $this->modificationsCount,
        ];
    }
}
