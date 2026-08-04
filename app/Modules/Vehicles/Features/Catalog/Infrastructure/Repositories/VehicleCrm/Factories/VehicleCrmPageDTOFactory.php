<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class VehicleCrmPageDTOFactory
{
    public function __construct(
        private VehicleCrmPaginationMetaDTOFactory $metaFactory,
    ) {}

    /**
     * @param  Collection<int, VehicleCrmListItemDTO>  $items
     */
    public function make(Collection $items, LengthAwarePaginator $paginator): VehicleCrmPageDTO
    {
        return new VehicleCrmPageDTO(
            data: $items,
            meta: $this->metaFactory->make($paginator),
        );
    }
}
