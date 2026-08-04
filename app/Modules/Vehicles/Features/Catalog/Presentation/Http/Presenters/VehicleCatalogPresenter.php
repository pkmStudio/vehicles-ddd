<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use Illuminate\Support\Collection;

final readonly class VehicleCatalogPresenter
{
    /**
     * @param  Collection<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    public function collection(Collection $items): array
    {
        return $items
            ->map(fn (mixed $item): array => $item->toArray())
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<string, float|int|string|null>>
     */
    public function modificationContext(CatalogModificationContextDTO $context): array
    {
        return [
            'manufacturer' => $context->manufacturer->toArray(),
            'vehicle' => $context->vehicle->toArray(),
            'modification' => $context->modification->toArray(),
        ];
    }
}
