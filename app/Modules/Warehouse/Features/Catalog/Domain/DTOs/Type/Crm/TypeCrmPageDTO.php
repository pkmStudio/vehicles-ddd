<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm;

use Illuminate\Support\Collection;

final readonly class TypeCrmPageDTO
{
    /**
     * @param  Collection<int, TypeCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public TypeCrmPaginationMetaDTO $meta,
    ) {}
}
