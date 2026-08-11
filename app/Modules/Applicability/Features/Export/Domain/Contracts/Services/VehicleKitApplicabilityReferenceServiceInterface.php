<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Services;

use Illuminate\Support\Collection;

interface VehicleKitApplicabilityReferenceServiceInterface
{
    /**
     * @return Collection<int, array<int, string>>
     */
    public function carcaseTypeRows(): Collection;
}
