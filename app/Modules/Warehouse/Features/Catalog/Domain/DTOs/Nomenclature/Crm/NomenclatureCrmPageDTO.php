<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm;

use Illuminate\Support\Collection;

/**
 * Постраничная CRM projection Warehouse-номенклатуры.
 */
final readonly class NomenclatureCrmPageDTO
{
    /**
     * @param  Collection<int, NomenclatureCrmListItemDTO>  $data
     */
    public function __construct(
        public Collection $data,
        public NomenclatureCrmPaginationMetaDTO $meta,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data
                ->map(fn (NomenclatureCrmListItemDTO $nomenclature): array => $nomenclature->toArray())
                ->values()
                ->all(),
            'meta' => $this->meta->toArray(),
        ];
    }
}
