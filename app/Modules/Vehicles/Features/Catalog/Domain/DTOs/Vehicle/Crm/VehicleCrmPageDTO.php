<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use Illuminate\Support\Collection;

/**
 * Постраничная CRM projection автомобилей вместе с meta пагинации.
 */
final readonly class VehicleCrmPageDTO
{
    /**
     * Хранит элементы страницы и meta пагинации.
     *
     * @param  Collection<int, VehicleCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public VehicleCrmPaginationMetaDTO $meta,
    ) {}

    /**
     * Возвращает публичный `data/meta` payload CRM read API.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data
                ->map(fn (VehicleCrmListItemDTO $vehicle): array => $vehicle->toArray())
                ->values()
                ->all(),
            'meta' => $this->meta->toArray(),
        ];
    }
}
