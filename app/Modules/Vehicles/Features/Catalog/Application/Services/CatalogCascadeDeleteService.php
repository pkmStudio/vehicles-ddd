<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Services;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\EngineModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogCascadeDeleteServiceInterface;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

final readonly class CatalogCascadeDeleteService implements CatalogCascadeDeleteServiceInterface
{
    public function __construct(
        private VehicleRepositoryInterface $vehicles,
        private ModificationRepositoryInterface $modifications,
        private EngineRepositoryInterface $engines,
        private PartSpecificationRepositoryInterface $partSpecifications,
        private VehicleCommandInterface $vehicleCommand,
        private ModificationCommandInterface $modificationCommand,
        private EngineModificationCommandInterface $engineModificationCommand,
        private PartSpecificationCommandInterface $partSpecificationCommand,
    ) {}

    public function deleteVehiclesByManufacturerId(int $manufacturerId): void
    {
        $this->deleteVehiclesByIds($this->vehicles->findIdsByManufacturerId($manufacturerId)->all());
    }

    /**
     * @param  array<int, int>  $vehicleIds
     */
    public function deleteVehiclesByIds(array $vehicleIds): void
    {
        $ids = $this->vehicleSubtreeIds($vehicleIds);

        if ($ids === []) {
            return;
        }

        $modificationIds = $this->modifications->findIdsByVehicleIds($ids)->all();
        $partSpecificationIds = $this->partSpecifications
            ->findIdsByPartable(PartableTypeEnum::VEHICLE, $ids)
            ->all();

        $this->deleteModificationDependencies($modificationIds);
        $this->modificationCommand->deleteByIds($modificationIds);
        $this->partSpecificationCommand->deleteByIds($partSpecificationIds);
        $this->vehicleCommand->deleteByIds($ids);
    }

    /**
     * @param  array<int, int>  $modificationIds
     */
    public function deleteModificationDependencies(array $modificationIds): void
    {
        if ($modificationIds === []) {
            return;
        }

        $engineModificationIds = $this->modifications
            ->findEngineModificationIdsByModificationIds($modificationIds)
            ->all();

        $this->engineModificationCommand->deleteByIds($engineModificationIds);
    }

    public function deleteEngineDependencies(int $engineId): void
    {
        $engineModificationIds = $this->engines->findEngineModificationIdsByEngineId($engineId)->all();
        $partSpecificationIds = $this->partSpecifications
            ->findIdsByPartable(PartableTypeEnum::ENGINE, [$engineId])
            ->all();

        $this->engineModificationCommand->deleteByIds($engineModificationIds);
        $this->partSpecificationCommand->deleteByIds($partSpecificationIds);
    }

    /**
     * @param  array<int, int>  $rootIds
     * @return array<int, int>
     */
    private function vehicleSubtreeIds(array $rootIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $rootIds)));
        $frontier = $ids;

        while ($frontier !== []) {
            $childIds = $this->vehicles->findChildIdsByParentIds($frontier)->all();
            $childIds = array_values(array_diff($childIds, $ids));

            if ($childIds === []) {
                break;
            }

            $ids = array_values(array_unique([...$ids, ...$childIds]));
            $frontier = $childIds;
        }

        return $ids;
    }
}
