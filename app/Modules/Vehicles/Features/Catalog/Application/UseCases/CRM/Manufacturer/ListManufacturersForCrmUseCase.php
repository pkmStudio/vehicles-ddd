<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerCrmReadQueryDTO;

/**
 * Оркестрирует CRM read-сценарий списка производителей.
 */
final readonly class ListManufacturersForCrmUseCase
{
    public function __construct(
        private ManufacturerCrmRepositoryInterface $manufacturers,
    ) {}

    public function execute(ManufacturerCrmReadQueryDTO $query): ManufacturerCrmPageDTO
    {
        return $this->manufacturers->paginate($query);
    }
}
