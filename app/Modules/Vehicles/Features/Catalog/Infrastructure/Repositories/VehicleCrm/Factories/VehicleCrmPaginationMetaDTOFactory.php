<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPaginationMetaDTO;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class VehicleCrmPaginationMetaDTOFactory
{
    public function make(LengthAwarePaginator $paginator): VehicleCrmPaginationMetaDTO
    {
        return new VehicleCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}
