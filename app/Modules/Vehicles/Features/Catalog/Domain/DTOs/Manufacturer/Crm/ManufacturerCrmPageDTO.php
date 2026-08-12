<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm;

use Illuminate\Support\Collection;

final readonly class ManufacturerCrmPageDTO
{
    /**
     * @param  Collection<int, ManufacturerCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public ManufacturerCrmPaginationMetaDTO $meta,
    ) {}
}
