<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm;

use Illuminate\Support\Collection;

final readonly class NomenclatureCrmPageDTO
{
    /**
     * @param  Collection<int, NomenclatureCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public NomenclatureCrmPaginationMetaDTO $meta,
    ) {}
}
