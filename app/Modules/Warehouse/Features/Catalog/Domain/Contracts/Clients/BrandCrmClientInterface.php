<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;

/**
 * Описывает read-only клиент CRM сценариев Warehouse-брендов.
 */
interface BrandCrmClientInterface
{
    public function paginate(BrandCrmReadQueryDTO $query): BrandCrmPageDTO;

    public function show(int $id): ?BrandCrmListItemDTO;
}
