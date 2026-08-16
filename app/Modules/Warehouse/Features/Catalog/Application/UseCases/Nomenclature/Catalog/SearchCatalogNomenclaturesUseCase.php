<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSearchResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSummaryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\Enums\CatalogNomenclatureSearchMatchEnum;

/**
 * Ищет номенклатуры публичного каталога и классифицирует результат поиска.
 */
final readonly class SearchCatalogNomenclaturesUseCase
{
    /**
     * Получает read repository публичного каталога.
     */
    public function __construct(
        private NomenclatureCatalogRepositoryInterface $repository,
    ) {}

    /**
     * Возвращает найденные позиции и признак точного, множественного или пустого результата.
     */
    public function execute(string $query, int $brandId, int $limit): CatalogNomenclatureSearchResultDTO
    {
        $items = $this->repository->search(
            query: $query,
            brandId: $brandId,
            limit: $limit,
        );
        $normalizedQuery = mb_strtolower($query);
        $matchesExactPartNumber = static fn (CatalogNomenclatureSummaryDTO $item): bool => mb_strtolower($item->partNumber) === $normalizedQuery;
        $hasExactPartNumber = $items->contains($matchesExactPartNumber);

        $match = match (true) {
            $items->isEmpty() => CatalogNomenclatureSearchMatchEnum::Empty,
            $hasExactPartNumber => CatalogNomenclatureSearchMatchEnum::Exact,
            default => CatalogNomenclatureSearchMatchEnum::Multiple,
        };

        return new CatalogNomenclatureSearchResultDTO(
            match: $match,
            items: $items,
        );
    }
}
