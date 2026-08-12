<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\Clients\CRM;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients\ManufacturerCrmClientInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Manufacturer\ListManufacturersForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Manufacturer\ShowManufacturerForCrmUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerCrmReadQueryDTO;

/**
 * Read-only клиент CRM сценариев производителей.
 */
final readonly class ManufacturerCrmClient implements ManufacturerCrmClientInterface
{
    public function __construct(
        private ListManufacturersForCrmUseCaseInterface $list,
        private ShowManufacturerForCrmUseCaseInterface $show,
    ) {}

    public function paginate(ManufacturerCrmReadQueryDTO $query): ManufacturerCrmPageDTO
    {
        return $this->list->execute($query);
    }

    public function show(int $id): ?ManufacturerCrmListItemDTO
    {
        return $this->show->execute($id);
    }
}
