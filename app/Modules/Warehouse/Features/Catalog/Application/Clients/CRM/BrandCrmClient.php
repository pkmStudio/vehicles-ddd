<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\BrandCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\Crm\ListBrandsForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\Crm\ShowBrandForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;

/**
 * Read-only клиент CRM сценариев Warehouse-брендов.
 */
final readonly class BrandCrmClient implements BrandCrmClientInterface
{
    public function __construct(
        private ListBrandsForCrmUseCaseInterface $list,
        private ShowBrandForCrmUseCaseInterface $show,
    ) {}

    public function paginate(BrandCrmReadQueryDTO $query): BrandCrmPageDTO
    {
        return $this->list->execute($query);
    }

    public function show(int $id): ?BrandCrmListItemDTO
    {
        return $this->show->execute($id);
    }
}
