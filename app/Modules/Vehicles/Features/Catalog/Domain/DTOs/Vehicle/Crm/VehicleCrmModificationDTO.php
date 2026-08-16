<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use App\Support\Http\Contracts\HttpArraySerializableInterface;
use Illuminate\Support\Collection;

/**
 * Сценарный снимок модификации автомобиля для CRM detail projection.
 */
final readonly class VehicleCrmModificationDTO implements HttpArraySerializableInterface
{
    /**
     * Хранит поля модификации и связанные двигатели для CRM detail ответа.
     *
     * @param  Collection<int, VehicleCrmEngineDTO>  $engines
     */
    public function __construct(
        public int $id,
        public int $vehicleId,
        public int $msId,
        public int $modId,
        public int $yearFrom,
        public ?int $yearTo,
        public string $description,
        public ?string $descriptionShort,
        public string $type,
        public ?string $brakeSystemType,
        public int $powerPs,
        public int $powerKw,
        public string $engineType,
        public ?string $gearType,
        public ?string $driveType,
        public ?string $localizedName,
        public ?int $numberOfCylinders,
        public ?float $capacityLt,
        public string $provider,
        public array $allowChangeFields,
        public Collection $engines,
    ) {}

    /**
     * Возвращает публичный payload модификации для CRM detail ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicleId,
            'ms_id' => $this->msId,
            'mod_id' => $this->modId,
            'year_from' => $this->yearFrom,
            'year_to' => $this->yearTo,
            'description' => $this->description,
            'description_short' => $this->descriptionShort,
            'type' => $this->type,
            'brake_system_type' => $this->brakeSystemType,
            'power_ps' => $this->powerPs,
            'power_kw' => $this->powerKw,
            'engine_type' => $this->engineType,
            'gear_type' => $this->gearType,
            'drive_type' => $this->driveType,
            'localized_name' => $this->localizedName,
            'number_of_cylinders' => $this->numberOfCylinders,
            'capacity_lt' => $this->capacityLt,
            'provider' => $this->provider,
            'allow_change_fields' => $this->allowChangeFields,
            'engines' => $this->engines
                ->map(fn (VehicleCrmEngineDTO $engine): array => $engine->toArray())
                ->values()
                ->all(),
        ];
    }
}
