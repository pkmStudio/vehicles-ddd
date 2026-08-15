<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadQueryDTO;

/**
 * Описывает read-only клиент CRM сценариев Warehouse-типов.
 */
interface TypeCrmClientInterface
{
    public function paginate(TypeCrmReadQueryDTO $query): TypeCrmPageDTO;

    public function show(int $id): ?TypeCrmListItemDTO;
}
