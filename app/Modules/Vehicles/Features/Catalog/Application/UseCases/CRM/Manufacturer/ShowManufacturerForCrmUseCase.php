<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmListItemDTO;

/**
 * Оркестрирует CRM read-сценарий detail-снимка производителя.
 */
final readonly class ShowManufacturerForCrmUseCase
{
    public function __construct(
        private ManufacturerCrmRepositoryInterface $manufacturers,
    ) {}

    public function execute(int $id): ?ManufacturerCrmListItemDTO
    {
        return $this->manufacturers->findById($id);
    }
}
