<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPartSpecificationDTO;
use Illuminate\Support\Collection;

final readonly class VehicleCrmReadPresenter
{
    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(VehicleCrmPageDTO $page): array
    {
        return [
            'data' => $this->collection($page->data),
            'meta' => $page->meta->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(VehicleCrmDetailDTO $detail): array
    {
        return $detail->vehicle->toArray() + [
            'modifications' => $detail->modifications
                ->map(fn (VehicleCrmModificationDTO $modification): array => $modification->toArray())
                ->values()
                ->all(),
            'part_specifications' => $detail->partSpecifications
                ->map(fn (VehicleCrmPartSpecificationDTO $specification): array => $specification->toArray())
                ->values()
                ->all(),
        ];
    }

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
}
