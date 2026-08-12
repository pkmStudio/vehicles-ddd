<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Brand\Crm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Brand\Crm\ListBrandsForCrmUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;

/**
 * Оркестрирует CRM read-сценарий списка Warehouse-брендов.
 */
final readonly class ListBrandsForCrmUseCase implements ListBrandsForCrmUseCaseInterface
{
    public function __construct(
        private BrandCrmRepositoryInterface $brands,
    ) {}

    public function execute(BrandCrmReadQueryDTO $query): BrandCrmPageDTO
    {
        return $this->brands->paginate($query);
    }
}
