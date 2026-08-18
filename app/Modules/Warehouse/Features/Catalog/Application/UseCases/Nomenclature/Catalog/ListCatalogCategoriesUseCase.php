<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use Illuminate\Support\Collection;

/**
 * Возвращает категории номенклатуры выбранного бренда для публичного каталога.
 */
final readonly class ListCatalogCategoriesUseCase
{
    /**
     * Получает read repository публичного каталога.
     */
    public function __construct(
        private NomenclatureCatalogRepositoryInterface $repository,
    ) {}

    /**
     * Возвращает непустые категории бренда с количеством номенклатур.
     *
     * @return Collection<int, CatalogCategoryDTO>
     */
    public function execute(int $brandId): Collection
    {
        return $this->repository->categories($brandId);
    }
}
