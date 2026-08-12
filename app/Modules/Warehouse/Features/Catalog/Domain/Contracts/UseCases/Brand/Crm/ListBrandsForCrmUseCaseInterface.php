<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;

/**
 * Описывает CRM read-сценарий списка Warehouse-брендов.
 */
interface ListBrandsForCrmUseCaseInterface
{
    public function execute(BrandCrmReadQueryDTO $query): BrandCrmPageDTO;
}
