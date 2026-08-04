<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPaginationMetaDTO;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Builds CRM pagination meta DTO from Laravel paginator.
 */
final readonly class NomenclatureCrmPaginationMetaDTOFactory
{
    public function make(LengthAwarePaginator $paginator): NomenclatureCrmPaginationMetaDTO
    {
        return new NomenclatureCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}
