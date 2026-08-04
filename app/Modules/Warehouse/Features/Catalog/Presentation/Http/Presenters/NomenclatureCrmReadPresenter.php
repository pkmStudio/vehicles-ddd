<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use Illuminate\Support\Collection;

final readonly class NomenclatureCrmReadPresenter
{
    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(NomenclatureCrmPageDTO $page): array
    {
        return [
            'data' => $this->collection($page->data),
            'meta' => $page->meta->toArray(),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    public function collection(Collection $items): array
    {
        return $items
            ->map(fn (mixed $item): array => $item->toArray())
            ->values()
            ->all();
    }
}
