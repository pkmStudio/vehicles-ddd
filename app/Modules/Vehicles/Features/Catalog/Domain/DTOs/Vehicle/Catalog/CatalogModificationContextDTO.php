<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog;

/**
 * Детальный контекст модификации с её производителем и ТС.
 */
final readonly class CatalogModificationContextDTO
{
    /**
     * Хранит связанные публичные проекции для detail endpoint модификации.
     */
    public function __construct(
        public CatalogManufacturerDTO $manufacturer,
        public CatalogVehicleDTO $vehicle,
        public CatalogModificationDTO $modification,
    ) {}
}
