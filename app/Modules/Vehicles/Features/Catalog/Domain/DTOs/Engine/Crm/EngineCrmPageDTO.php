<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm;

use Illuminate\Support\Collection;

final readonly class EngineCrmPageDTO
{
    /**
     * @param  Collection<int, EngineCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public EngineCrmPaginationMetaDTO $meta,
    ) {}
}
