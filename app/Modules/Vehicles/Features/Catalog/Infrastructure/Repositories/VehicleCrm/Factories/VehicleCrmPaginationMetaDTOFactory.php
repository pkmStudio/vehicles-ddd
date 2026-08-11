<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPaginationMetaDTO;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Маппит Laravel paginator в CRM pagination meta DTO.
 */
final readonly class VehicleCrmPaginationMetaDTOFactory
{
    /**
     * Создает pagination meta DTO.
     *
     * Шаги:
     * 1. Читает current page, per-page, total и last page из paginator.
     * 2. Возвращает typed meta DTO без Laravel paginator за repository boundary.
     */
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
