<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Applicability\Features\Export\Domain\DTOs\VehicleKitApplicabilityRowDTO;
use Illuminate\Support\Collection;

interface KitApplicabilityExportRepositoryInterface
{
    /** @return Collection<int, VehicleKitApplicabilityRowDTO> */
    public function vehicleRows(): Collection;
}
