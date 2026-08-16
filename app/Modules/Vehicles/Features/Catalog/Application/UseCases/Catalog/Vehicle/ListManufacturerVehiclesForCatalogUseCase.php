<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogVehicleDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

/**
 * Возвращает REST-список разрешённых ТС производителя.
 */
final readonly class ListManufacturerVehiclesForCatalogUseCase
{
    /**
     * Подключает репозитории производителя и его ТС.
     *
     * Шаги:
     * - Сохранить зависимости для проверки производителя и чтения разрешённых ТС.
     */
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
        private VehicleRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает разрешённые ТС производителя или null для неизвестного производителя.
     *
     * Шаги:
     * - Проверить существование производителя по catalog id.
     * - Вернуть null, если производитель не найден.
     * - Прочитать разрешённые ТС производителя и преобразовать их в DTO публичного каталога.
     *
     * @return Collection<int, CatalogVehicleDTO>|null
     */
    public function execute(int $manufacturerId): ?Collection
    {
        if ($this->manufacturers->findById($manufacturerId) === null) {
            return null;
        }

        return $this->vehicles
            ->findAllowedByManufacturerId($manufacturerId)
            ->map(static fn (VehicleData $vehicle): CatalogVehicleDTO => CatalogVehicleDTO::fromData($vehicle))
            ->values();
    }
}
