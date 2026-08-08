<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog\SearchCatalogNomenclaturesUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSearchResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSummaryDTO;

final readonly class SearchCatalogNomenclaturesUseCase implements SearchCatalogNomenclaturesUseCaseInterface
{
    public function __construct(
        private NomenclatureCatalogRepositoryInterface $repository,
    ) {}

    public function execute(string $query, int $brandId, int $limit): CatalogNomenclatureSearchResultDTO
    {
        $items = $this->repository->search($query, $brandId, $limit);
        $hasExactPartNumber = $items->contains(
            static fn (CatalogNomenclatureSummaryDTO $item): bool => mb_strtolower($item->partNumber) === mb_strtolower($query),
        );

        return new CatalogNomenclatureSearchResultDTO(
            match: $items->isEmpty() ? 'empty' : ($hasExactPartNumber ? 'exact' : 'multiple'),
            items: $items,
        );
    }
}
