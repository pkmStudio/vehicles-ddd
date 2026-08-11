<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPartSpecificationDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

final readonly class VehicleCrmReadPresenter
{
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function page(VehicleCrmPageDTO $page): array
    {
        return $this->arrays->page($page->data, $page->meta);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(VehicleCrmDetailDTO $detail): array
    {
        return $this->arrays->item($detail->vehicle) + [
            'modifications' => $detail->modifications
                ->map(fn (VehicleCrmModificationDTO $modification): array => $this->arrays->item($modification))
                ->values()
                ->all(),
            'part_specifications' => $detail->partSpecifications
                ->map(fn (VehicleCrmPartSpecificationDTO $specification): array => $this->arrays->item($specification))
                ->values()
                ->all(),
        ];
    }
}
