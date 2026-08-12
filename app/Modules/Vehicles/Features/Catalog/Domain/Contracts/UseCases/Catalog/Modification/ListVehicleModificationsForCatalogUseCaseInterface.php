<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationDTO;
use Illuminate\Support\Collection;

/**
 * Use case port REST-списка модификаций ТС публичного каталога.
 */
interface ListVehicleModificationsForCatalogUseCaseInterface
{
    /**
     * Возвращает модификации разрешенного автомобиля публичного каталога.
     *
     * Шаги:
     * 1) Проверить автомобиль по catalog id.
     * 2) Вернуть null для отсутствующего/закрытого автомобиля или collection modification DTO.
     *
     * @return Collection<int, CatalogModificationDTO>|null
     */
    public function execute(int $vehicleId): ?Collection;
}
