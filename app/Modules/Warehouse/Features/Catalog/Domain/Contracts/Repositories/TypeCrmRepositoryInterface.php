<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadQueryDTO;

interface TypeCrmRepositoryInterface
{
    public function paginate(TypeCrmReadQueryDTO $query): TypeCrmPageDTO;

    public function findById(int $id): ?TypeCrmListItemDTO;
}
