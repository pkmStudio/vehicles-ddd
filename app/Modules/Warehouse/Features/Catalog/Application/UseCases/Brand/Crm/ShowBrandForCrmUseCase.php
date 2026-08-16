<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;

/**
 * Оркестрирует CRM read-сценарий detail-снимка Warehouse-бренда.
 */
final readonly class ShowBrandForCrmUseCase
{
    public function __construct(
        private BrandCrmRepositoryInterface $brands,
    ) {}

    public function execute(int $id): ?BrandCrmListItemDTO
    {
        return $this->brands->findById($id);
    }
}
