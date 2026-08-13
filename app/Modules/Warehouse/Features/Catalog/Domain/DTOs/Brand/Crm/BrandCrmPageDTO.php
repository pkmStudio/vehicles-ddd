<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm;

use Illuminate\Support\Collection;

final readonly class BrandCrmPageDTO
{
    /**
     * @param  Collection<int, BrandCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public BrandCrmPaginationMetaDTO $meta,
    ) {}
}
