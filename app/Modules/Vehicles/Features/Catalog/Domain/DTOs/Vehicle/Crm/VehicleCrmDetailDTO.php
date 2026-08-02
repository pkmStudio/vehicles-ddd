<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use Illuminate\Support\Collection;

/**
 * Detail projection автомобиля для CRM read API.
 */
final readonly class VehicleCrmDetailDTO
{
    /**
     * Хранит базовые поля автомобиля и вложенные CRM detail коллекции.
     *
     * @param  Collection<int, VehicleCrmModificationDTO>  $modifications
     * @param  Collection<int, VehicleCrmPartSpecificationDTO>  $partSpecifications
     */
    public function __construct(
        public VehicleCrmListItemDTO $vehicle,
        public Collection $modifications,
        public Collection $partSpecifications,
    ) {}

    /**
     * Возвращает публичный detail payload CRM read API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->vehicle->toArray() + [
            'modifications' => $this->modifications
                ->map(fn (VehicleCrmModificationDTO $modification): array => $modification->toArray())
                ->values()
                ->all(),
            'part_specifications' => $this->partSpecifications
                ->map(fn (VehicleCrmPartSpecificationDTO $specification): array => $specification->toArray())
                ->values()
                ->all(),
        ];
    }
}
