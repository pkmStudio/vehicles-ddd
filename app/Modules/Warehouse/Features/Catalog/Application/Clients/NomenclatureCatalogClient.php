<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Clients;

use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog\ListCatalogCategoriesUseCase;
use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog\ListCategoryNomenclaturesUseCase;
use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog\SearchCatalogNomenclaturesUseCase;
use App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog\ShowCatalogNomenclatureUseCase;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients\NomenclatureCatalogClientInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclaturePageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSearchResultDTO;
use Illuminate\Support\Collection;

/**
 * Read-only фасад catalog use cases складской номенклатуры.
 */
final readonly class NomenclatureCatalogClient implements NomenclatureCatalogClientInterface
{
    /**
     * Получает use cases списка категорий, номенклатур, detail и поиска.
     */
    public function __construct(
        private ListCatalogCategoriesUseCase $listCategories,
        private ListCategoryNomenclaturesUseCase $listNomenclatures,
        private ShowCatalogNomenclatureUseCase $showNomenclature,
        private SearchCatalogNomenclaturesUseCase $searchNomenclatures,
    ) {}

    /**
     * Делегирует чтение категорий catalog use case.
     *
     * @return Collection<int, CatalogCategoryDTO>
     */
    public function categories(int $brandId): Collection
    {
        return $this->listCategories->execute($brandId);
    }

    /**
     * Делегирует чтение страницы номенклатур catalog use case.
     */
    public function nomenclatures(int $categoryId, int $brandId, int $page, int $pageSize): ?CatalogNomenclaturePageDTO
    {
        return $this->listNomenclatures->execute(
            categoryId: $categoryId,
            brandId: $brandId,
            page: $page,
            pageSize: $pageSize,
        );
    }

    /**
     * Делегирует чтение detail номенклатуры catalog use case.
     */
    public function nomenclature(string $partNumber, int $brandId): ?CatalogNomenclatureDTO
    {
        return $this->showNomenclature->execute(
            partNumber: $partNumber,
            brandId: $brandId,
        );
    }

    /**
     * Делегирует поиск номенклатур catalog use case.
     */
    public function search(string $query, int $brandId, int $limit): CatalogNomenclatureSearchResultDTO
    {
        return $this->searchNomenclatures->execute(
            query: $query,
            brandId: $brandId,
            limit: $limit,
        );
    }
}
