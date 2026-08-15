<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Clients\CRM;

use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Type\Crm\ListTypesForCrmUseCase;
use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Type\Crm\ShowTypeForCrmUseCase;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\TypeCrmClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadQueryDTO;

/**
 * Read-only клиент CRM сценариев Warehouse-типов.
 */
final readonly class TypeCrmClient implements TypeCrmClientInterface
{
    public function __construct(
        private ListTypesForCrmUseCase $list,
        private ShowTypeForCrmUseCase $show,
    ) {}

    public function paginate(TypeCrmReadQueryDTO $query): TypeCrmPageDTO
    {
        return $this->list->execute($query);
    }

    public function show(int $id): ?TypeCrmListItemDTO
    {
        return $this->show->execute($id);
    }
}
