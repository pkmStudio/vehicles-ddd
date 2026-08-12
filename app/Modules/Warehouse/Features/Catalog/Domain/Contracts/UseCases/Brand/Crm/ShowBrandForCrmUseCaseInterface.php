<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;

/**
 * Описывает CRM read-сценарий detail-снимка Warehouse-бренда.
 */
interface ShowBrandForCrmUseCaseInterface
{
    public function execute(int $id): ?BrandCrmListItemDTO;
}
