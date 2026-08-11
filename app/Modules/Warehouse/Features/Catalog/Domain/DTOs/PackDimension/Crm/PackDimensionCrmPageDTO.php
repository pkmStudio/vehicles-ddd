<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm;

use Illuminate\Support\Collection;

final readonly class PackDimensionCrmPageDTO
{
    /**
     * @param  Collection<int, PackDimensionCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public PackDimensionCrmPaginationMetaDTO $meta,
    ) {}
}
