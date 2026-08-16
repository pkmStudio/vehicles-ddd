<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Support\Http\Contracts\HttpArraySerializableInterface;

/**
 * Публичная REST-проекция ТС для каталога.
 */
final readonly class CatalogVehicleDTO implements HttpArraySerializableInterface
{
    /**
     * Хранит публичные поля ТС для ответа каталога.
     */
    public function __construct(
        public int $id,
        public int $msId,
        public int $manufacturerId,
        public string $name,
        public ?string $localizedName,
        public string $generation,
        public ?string $generationShort,
        public string $typeCarcase,
        public int $yearFrom,
        public ?int $yearTo,
    ) {}

    /**
     * Собирает публичную проекцию ТС из Data-снимка.
     */
    public static function fromData(VehicleData $vehicle): self
    {
        return new self(
            id: (int) $vehicle->id,
            msId: $vehicle->msId,
            manufacturerId: $vehicle->manufacturerId,
            name: $vehicle->name,
            localizedName: $vehicle->localizedName,
            generation: $vehicle->generation,
            generationShort: $vehicle->generationShort,
            typeCarcase: $vehicle->typeCarcase->value,
            yearFrom: $vehicle->generationYearFrom,
            yearTo: $vehicle->generationYearTo,
        );
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ms_id' => $this->msId,
            'manufacturer_id' => $this->manufacturerId,
            'name' => $this->name,
            'localized_name' => $this->localizedName,
            'generation' => $this->generation,
            'generation_short' => $this->generationShort,
            'type_carcase' => $this->typeCarcase,
            'year_from' => $this->yearFrom,
            'year_to' => $this->yearTo,
        ];
    }
}
