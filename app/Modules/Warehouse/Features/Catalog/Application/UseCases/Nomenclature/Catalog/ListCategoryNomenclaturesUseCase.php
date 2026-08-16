<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclaturePageDTO;

/**
 * Возвращает страницу номенклатур выбранной категории и бренда.
 */
final readonly class ListCategoryNomenclaturesUseCase
{
    /**
     * Получает read repository публичного каталога.
     */
    public function __construct(
        private NomenclatureCatalogRepositoryInterface $repository,
    ) {}

    /**
     * Делегирует пагинированное чтение repository.
     */
    public function execute(int $categoryId, int $brandId, int $page, int $pageSize): ?CatalogNomenclaturePageDTO
    {
        $category = $this->repository->findCategory(
            categoryId: $categoryId,
            brandId: $brandId,
        );

        if ($category === null) {
            return null;
        }

        $items = $this->repository->findByCategory(
            categoryId: $categoryId,
            brandId: $brandId,
            page: $page,
            pageSize: $pageSize,
        );
        $pageCount = (int) ceil($category->nomenclatureCount / $pageSize);

        return new CatalogNomenclaturePageDTO(
            category: $category,
            items: $items,
            total: $category->nomenclatureCount,
            page: $page,
            pageSize: $pageSize,
            pageCount: $pageCount,
        );
    }
}
