<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Type\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\TypeCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadQueryDTO;

/**
 * Оркестрирует CRM read-сценарий списка Warehouse-типов.
 */
final readonly class ListTypesForCrmUseCase
{
    public function __construct(
        private TypeCrmRepositoryInterface $types,
    ) {}

    public function execute(TypeCrmReadQueryDTO $query): TypeCrmPageDTO
    {
        return $this->types->paginate($query);
    }
}
