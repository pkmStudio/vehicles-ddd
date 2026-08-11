<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehiclesApplicabilityRepositoryInterface;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Vehicles\Shared\Domain\Exceptions\VehicleApplicabilityException;
use Illuminate\Support\Collection;

final readonly class VehiclesApplicabilityClient implements VehiclesApplicabilityClientInterface
{
    public function __construct(
        private VehiclesApplicabilityRepositoryInterface $vehicles,
    ) {}

    public function frontWiperSpecifications(int $lengthMain, ?int $lengthSecond, int $countWipers): Collection
    {
        return $this->vehicles->frontWiperSpecifications(
            lengthMain: $lengthMain,
            lengthSecond: $lengthSecond,
            countWipers: $countWipers,
        );
    }

    public function rearWiperSpecifications(int $lengthMain, int $countWipers): Collection
    {
        return $this->vehicles->rearWiperSpecifications(
            lengthMain: $lengthMain,
            countWipers: $countWipers,
        );
    }

    public function resolveModificationIdByMsAndModId(int $msId, int $modId): int
    {
        $vehicle = $this->vehicles->findVehicleByMsId($msId);

        if ($vehicle === null) {
            throw new VehicleApplicabilityException("Модель (ms_id: {$msId}) не найдена.");
        }

        $modificationId = $this->vehicles->findModificationIdByMsAndModId($vehicle->msId, $modId);

        if ($modificationId !== null) {
            return $modificationId;
        }

        if ($vehicle->parentId !== null) {
            return $this->resolveParentModificationId($vehicle->msId, $vehicle->parentId, $modId);
        }

        throw new VehicleApplicabilityException("Модификация (ms_id: {$vehicle->msId}, mod_id: {$modId}) не найдена.");
    }

    private function resolveParentModificationId(int $vehicleMsId, int $parentId, int $modId): int
    {
        $parentMsId = $this->vehicles->findVehicleMsIdById($parentId);

        if ($parentMsId === null) {
            throw new VehicleApplicabilityException("Модификация (ms_id: {$vehicleMsId}, mod_id: {$modId}) не найдена.");
        }

        $modificationId = $this->vehicles->findModificationIdByMsAndModId($parentMsId, $modId);

        if ($modificationId !== null) {
            return $modificationId;
        }

        throw new VehicleApplicabilityException(
            "Модификация (ms_id: {$vehicleMsId}, mod_id: {$modId}) не найдена ни у модели, ни у родителя (parent_ms_id: {$parentMsId}).",
        );
    }
}
