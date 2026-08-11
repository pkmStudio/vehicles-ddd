<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Applicability\VehicleApplicabilityLookupDTO;
use App\Modules\Vehicles\Shared\Domain\DTOs\Applicability\VehiclePartSpecificationForApplicabilityDTO;
use Illuminate\Support\Collection;

interface VehiclesApplicabilityRepositoryInterface
{
    /**
     * @return Collection<int, VehiclePartSpecificationForApplicabilityDTO>
     */
    public function frontWiperSpecifications(int $lengthMain, ?int $lengthSecond, int $countWipers): Collection;

    /**
     * @return Collection<int, VehiclePartSpecificationForApplicabilityDTO>
     */
    public function rearWiperSpecifications(int $lengthMain, int $countWipers): Collection;

    public function findVehicleByMsId(int $msId): ?VehicleApplicabilityLookupDTO;

    public function findVehicleMsIdById(int $id): ?int;

    public function findModificationIdByMsAndModId(int $msId, int $modId): ?int;
}
