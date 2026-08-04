<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Builds CRM page DTO from list item collection and paginator.
 */
final readonly class NomenclatureCrmPageDTOFactory
{
    public function __construct(
        private NomenclatureCrmPaginationMetaDTOFactory $metaFactory,
    ) {}

    /**
     * @param  Collection<int, NomenclatureCrmListItemDTO>  $items
     */
    public function make(Collection $items, LengthAwarePaginator $paginator): NomenclatureCrmPageDTO
    {
        return new NomenclatureCrmPageDTO(
            data: $items,
            meta: $this->metaFactory->make($paginator),
        );
    }
}
