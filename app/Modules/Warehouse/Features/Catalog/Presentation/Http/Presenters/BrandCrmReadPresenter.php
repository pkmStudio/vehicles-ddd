<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

/**
 * Преобразует CRM DTO Warehouse-брендов в HTTP response arrays.
 */
final readonly class BrandCrmReadPresenter
{
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(BrandCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(BrandCrmListItemDTO $detail): array
    {
        return $this->arrays->item($detail);
    }
}
