<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog\ListCatalogCategoriesUseCaseInterface;
use Illuminate\Support\Collection;

final readonly class ListCatalogCategoriesUseCase implements ListCatalogCategoriesUseCaseInterface
{
    public function __construct(
        private NomenclatureCatalogRepositoryInterface $repository,
    ) {}

    public function execute(int $brandId): Collection
    {
        return $this->repository->categories($brandId);
    }
}
