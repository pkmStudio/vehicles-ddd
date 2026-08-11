<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm;

use Illuminate\Support\Collection;

final readonly class KitCrmPageDTO
{
    /**
     * @param  Collection<int, KitCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public KitCrmPaginationMetaDTO $meta,
    ) {}
}
