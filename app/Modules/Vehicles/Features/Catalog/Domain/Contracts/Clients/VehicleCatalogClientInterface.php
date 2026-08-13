<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogManufacturerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationContextDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogVehicleDTO;
use Illuminate\Support\Collection;

interface VehicleCatalogClientInterface
{
    /**
     * Возвращает производителей, доступных в публичном каталоге Vehicles.
     *
     * Шаги:
     * 1) Прочитать производителей, у которых есть разрешенные к показу автомобили.
     * 2) Вернуть collection catalog DTO без infrastructure models.
     *
     * @return Collection<int, CatalogManufacturerDTO>
     */
    public function manufacturers(): Collection;

    /**
     * Возвращает разрешенные автомобили указанного производителя.
     *
     * Шаги:
     * 1) Проверить производителя по catalog id.
     * 2) Вернуть null для неизвестного производителя или collection catalog DTO для найденного.
     *
     * @return null|Collection<int, CatalogVehicleDTO>
     */
    public function vehicles(int $manufacturerId): ?Collection;

    /**
     * Возвращает модификации разрешенного автомобиля публичного каталога.
     *
     * Шаги:
     * 1) Проверить автомобиль по catalog id и его is_allow доступность.
     * 2) Вернуть null для недоступного автомобиля или collection modification DTO.
     *
     * @return null|Collection<int, CatalogModificationDTO>
     */
    public function modifications(int $vehicleId): ?Collection;

    /**
     * Возвращает detail-контекст модификации вместе с ее автомобилем и производителем.
     *
     * Шаги:
     * 1) Найти модификацию по catalog id.
     * 2) Проверить доступность связанного автомобиля и производителя.
     * 3) Вернуть context DTO или null, если цепочка каталога неполная.
     */
    public function modification(int $modificationId): ?CatalogModificationContextDTO;
}
