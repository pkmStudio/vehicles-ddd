<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Type\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\TypeCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmListItemDTO;

/**
 * Оркестрирует CRM read-сценарий detail-снимка Warehouse-типа.
 */
final readonly class ShowTypeForCrmUseCase
{
    public function __construct(
        private TypeCrmRepositoryInterface $types,
    ) {}

    public function execute(int $id): ?TypeCrmListItemDTO
    {
        return $this->types->findById($id);
    }
}
