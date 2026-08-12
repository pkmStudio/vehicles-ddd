<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;

/**
 * Описывает read port Warehouse-брендов для CRM API.
 */
interface BrandCrmRepositoryInterface
{
    public function paginate(BrandCrmReadQueryDTO $query): BrandCrmPageDTO;

    public function findById(int $id): ?BrandCrmListItemDTO;
}
