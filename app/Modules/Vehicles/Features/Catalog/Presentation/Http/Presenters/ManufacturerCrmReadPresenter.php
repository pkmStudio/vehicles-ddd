<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPageDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

/**
 * Преобразует CRM DTO производителей в HTTP response arrays.
 */
final readonly class ManufacturerCrmReadPresenter
{
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(ManufacturerCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(ManufacturerCrmListItemDTO $detail): array
    {
        return $this->arrays->item($detail);
    }
}
