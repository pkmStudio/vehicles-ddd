<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Сценарный снимок двигателя, привязанного к конкретной модификации CRM автомобиля.
 */
final readonly class VehicleCrmModificationEngineDTO implements HttpArraySerializableInterface
{
    public function __construct(
        public int $modificationId,
        public int $id,
        public int $engId,
        public string $codeEngine,
        public int $powerKwStart,
        public int $powerPsStart,
        public string $fuelType,
        public string $provider,
        public ?string $engineCapacity = null,
        public ?int $cylinderCount = null,
        public ?float $cylinderDiameter = null,
        public ?int $powerKwUpto = null,
        public ?int $powerPsUpto = null,
        public ?int $numberOfValves = null,
        public ?int $groupId = null,
        public array $allowChangeFields = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'modification_id' => $this->modificationId,
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
}
