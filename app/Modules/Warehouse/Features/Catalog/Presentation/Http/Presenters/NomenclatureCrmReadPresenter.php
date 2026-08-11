<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

final readonly class NomenclatureCrmReadPresenter
{
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(NomenclatureCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(NomenclatureCrmListItemDTO $detail): array
    {
        return $this->arrays->item($detail);
    }
}
