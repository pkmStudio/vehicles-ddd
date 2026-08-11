<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\VehicleCatalogClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Manufacturer\ListManufacturersForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ListVehicleModificationsForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification\ShowModificationForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Vehicle\ListManufacturerVehiclesForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use Illuminate\Support\Collection;

final readonly class VehicleCatalogClient implements VehicleCatalogClientInterface
{
    /**
     * Инициализирует catalog/store read client для Vehicles.
     *
     * Шаги:
     * 1. Получает use case производителей, автомобилей, модификаций и detail context.
     * 2. Сохраняет зависимости как read-only фасад catalog сценариев.
     */
    public function __construct(
        private ListManufacturersForCatalogUseCaseInterface $listManufacturers,
        private ListManufacturerVehiclesForCatalogUseCaseInterface $listVehicles,
        private ListVehicleModificationsForCatalogUseCaseInterface $listModifications,
        private ShowModificationForCatalogUseCaseInterface $showModification,
    ) {}

    /**
     * Возвращает производителей, доступных во внешнем каталоге.
     *
     * Шаги:
     * 1. Делегирует запрос use case списка производителей.
     * 2. Возвращает collection DTO без доступа к БД из client.
     */
    public function manufacturers(): Collection
    {
        return $this->listManufacturers->execute();
    }

    /**
     * Возвращает автомобили производителя для внешнего каталога.
     *
     * Шаги:
     * 1. Принимает id производителя.
     * 2. Делегирует запрос use case списка автомобилей.
     * 3. Возвращает collection DTO или `null`, если производитель не найден.
     */
    public function vehicles(int $manufacturerId): ?Collection
    {
        return $this->listVehicles->execute($manufacturerId);
    }

    /**
     * Возвращает модификации автомобиля для внешнего каталога.
     *
     * Шаги:
     * 1. Принимает id автомобиля.
     * 2. Делегирует запрос use case списка модификаций.
     * 3. Возвращает collection DTO или `null`, если автомобиль не найден.
     */
    public function modifications(int $vehicleId): ?Collection
    {
        return $this->listModifications->execute($vehicleId);
    }

    /**
     * Возвращает модификацию вместе с контекстом автомобиля и производителя.
     *
     * Шаги:
     * 1. Принимает id модификации.
     * 2. Делегирует запрос use case detail context.
     * 3. Возвращает DTO или `null`, если модификация не найдена.
     */
    public function modification(int $modificationId): ?CatalogModificationContextDTO
    {
        return $this->showModification->execute($modificationId);
    }
}
