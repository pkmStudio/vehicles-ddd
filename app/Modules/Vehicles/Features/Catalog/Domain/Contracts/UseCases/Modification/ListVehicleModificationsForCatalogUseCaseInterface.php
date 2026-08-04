<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogModificationDTO;
use Illuminate\Support\Collection;

/**
 * Use case port REST-списка модификаций ТС публичного каталога.
 */
interface ListVehicleModificationsForCatalogUseCaseInterface
{
    /** @return Collection<int, CatalogModificationDTO>|null */
    public function execute(int $vehicleId): ?Collection;
}
