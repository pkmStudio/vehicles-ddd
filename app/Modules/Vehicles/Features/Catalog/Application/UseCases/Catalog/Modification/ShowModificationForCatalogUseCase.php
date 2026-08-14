<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogManufacturerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogVehicleDTO;

/**
 * Возвращает детальную REST-форму модификации с её ТС и производителем.
 */
final readonly class ShowModificationForCatalogUseCase
{
    /**
     * Подключает репозитории для сборки детального контекста модификации.
     *
     * Шаги:
     * - Сохранить зависимости для чтения модификации, её ТС и производителя.
     */
    public function __construct(
        private ModificationRepositoryInterface $modifications,
        private VehicleRepositoryInterface $vehicles,
        private ManufacturerRepositoryInterface $manufacturers,
    ) {}

    /**
     * Возвращает детальный контекст модификации публичного каталога.
     *
     * Шаги:
     * - Найти модификацию по catalog id.
     * - Найти связанное ТС и убедиться, что оно разрешено к показу.
     * - Найти производителя ТС.
     * - Собрать DTO контекста из производителя, ТС и модификации.
     */
    public function execute(int $modificationId): ?CatalogModificationContextDTO
    {
        $modification = $this->modifications->findById($modificationId);

        if ($modification === null) {
            return null;
        }

        $vehicle = $this->vehicles->findById($modification->vehicleId);

        if ($vehicle === null || ! $vehicle->isAllow) {
            return null;
        }

        $manufacturer = $this->manufacturers->findById($vehicle->manufacturerId);

        if ($manufacturer === null) {
            return null;
        }

        return new CatalogModificationContextDTO(
            manufacturer: CatalogManufacturerDTO::fromData($manufacturer),
            vehicle: CatalogVehicleDTO::fromData($vehicle),
            modification: CatalogModificationDTO::fromData($modification),
        );
    }
}
