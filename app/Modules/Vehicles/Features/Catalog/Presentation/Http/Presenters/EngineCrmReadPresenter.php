<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\Crm\EngineCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

/**
 * Преобразует CRM DTO двигателей в HTTP response arrays.
 */
final readonly class EngineCrmReadPresenter
{
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(EngineCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(EngineCrmListItemDTO $detail): array
    {
        return $this->arrays->item($detail);
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function relationPage(VehicleCrmRelationPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }
}
