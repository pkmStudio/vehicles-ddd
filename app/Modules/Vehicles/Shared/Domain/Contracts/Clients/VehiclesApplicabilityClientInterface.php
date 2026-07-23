<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Contracts\Clients;

use Illuminate\Support\Collection;

interface VehiclesApplicabilityClientInterface
{
    /**
     * @return Collection<int, \App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO>
     */
    public function frontWiperSpecifications(int $lengthMain, ?int $lengthSecond, int $countWipers): Collection;

    /**
     * @return Collection<int, \App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO>
     */
    public function rearWiperSpecifications(int $lengthMain, int $countWipers): Collection;

    public function resolveModificationIdByMsAndModId(int $msId, int $modId): int;
}
