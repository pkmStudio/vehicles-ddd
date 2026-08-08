<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog\ListCategoryNomenclaturesUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclaturePageDTO;

final readonly class ListCategoryNomenclaturesUseCase implements ListCategoryNomenclaturesUseCaseInterface
{
    public function __construct(
        private NomenclatureCatalogRepositoryInterface $repository,
    ) {}

    public function execute(int $categoryId, int $brandId, int $page, int $pageSize): ?CatalogNomenclaturePageDTO
    {
        return $this->repository->paginateByCategory($categoryId, $brandId, $page, $pageSize);
    }
}
