<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogManufacturerDTO;
use Illuminate\Support\Collection;

/**
 * Use case port REST-списка производителей публичного каталога.
 */
interface ListManufacturersForCatalogUseCaseInterface
{
    /**
     * Возвращает производителей, у которых есть разрешенные автомобили публичного каталога.
     *
     * Шаги:
     * 1) Прочитать производителей через catalog read boundary.
     * 2) Вернуть collection DTO для REST response.
     *
     * @return Collection<int, CatalogManufacturerDTO>
     */
    public function execute(): Collection;
}
