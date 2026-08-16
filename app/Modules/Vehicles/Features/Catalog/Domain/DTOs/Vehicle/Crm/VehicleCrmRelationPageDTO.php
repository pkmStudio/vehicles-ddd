<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

use App\Support\Http\Contracts\HttpArraySerializableInterface;
use Illuminate\Support\Collection;

final readonly class VehicleCrmRelationPageDTO
{
    /**
     * @param  Collection<int, HttpArraySerializableInterface>  $data
     */
    public function __construct(
        public Collection $data,
        public VehicleCrmPaginationMetaDTO $meta,
    ) {}
}
