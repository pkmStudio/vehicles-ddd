<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use App\Support\Http\Presenters\HttpArrayPresenter;

final readonly class VehicleCatalogPresenter
{
    public function __construct(
        private HttpArrayPresenter $arrays,
    ) {}

    /**
     * @return array<string, array<string, float|int|string|null>>
     */
    public function modificationContext(CatalogModificationContextDTO $context): array
    {
        return [
            'manufacturer' => $this->arrays->item($context->manufacturer),
            'vehicle' => $this->arrays->item($context->vehicle),
            'modification' => $this->arrays->item($context->modification),
        ];
    }
}
