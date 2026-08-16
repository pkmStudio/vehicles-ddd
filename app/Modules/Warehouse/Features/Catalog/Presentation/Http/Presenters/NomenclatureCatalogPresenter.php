<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclaturePageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSearchResultDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSummaryDTO;
use Illuminate\Support\Collection;

/** Собирает HTTP shape публичного каталога Warehouse. */
final readonly class NomenclatureCatalogPresenter
{
    /**
     * @param  Collection<int, CatalogCategoryDTO>  $categories
     * @return list<array{id: int, name: string, code: string|null, nomenclature_count: int}>
     */
    public function categories(Collection $categories): array
    {
        return $categories
            ->map(fn (CatalogCategoryDTO $category): array => $this->category($category))
            ->values()
            ->all();
    }

    /**
     * @return array{category: array{id: int, name: string, code: string|null, nomenclature_count: int}, items: list<array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string}>, total: int, page: int, page_size: int, page_count: int}
     */
    public function page(CatalogNomenclaturePageDTO $page): array
    {
        return [
            'category' => $this->category($page->category),
            'items' => $page->items
                ->map(fn (CatalogNomenclatureSummaryDTO $item): array => $this->summary($item))
                ->values()
                ->all(),
            'total' => $page->total,
            'page' => $page->page,
            'page_size' => $page->pageSize,
            'page_count' => $page->pageCount,
        ];
    }

    /**
     * @return array{part_number: string, name: string, category_id: int, category_name: string, category_code: string|null, brand_id: int, brand_name: string, brand_code: string|null, country: string, color: string, weight: int, material: list<string>, vehicle_type: list<string>, quantity_pak: int, quantity_in_pak: int, details: array<string, bool|float|int|string|null|array<int|string, bool|float|int|string|null>>}
     */
    public function nomenclature(CatalogNomenclatureDTO $nomenclature): array
    {
        return [
            'part_number' => $nomenclature->partNumber,
            'name' => $nomenclature->name,
            'category_id' => $nomenclature->categoryId,
            'category_name' => $nomenclature->categoryName,
            'category_code' => $nomenclature->categoryCode,
            'brand_id' => $nomenclature->brandId,
            'brand_name' => $nomenclature->brandName,
            'brand_code' => $nomenclature->brandCode,
            'country' => $nomenclature->country,
            'color' => $nomenclature->color,
            'weight' => $nomenclature->weight,
            'material' => $nomenclature->material,
            'vehicle_type' => $nomenclature->vehicleType,
            'quantity_pak' => $nomenclature->quantityPak,
            'quantity_in_pak' => $nomenclature->quantityInPak,
            'details' => $nomenclature->details,
        ];
    }

    /** @return array{match: string, items: list<array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string}>} */
    public function search(CatalogNomenclatureSearchResultDTO $result): array
    {
        return [
            'match' => $result->match->value,
            'items' => $result->items
                ->map(fn (CatalogNomenclatureSummaryDTO $item): array => $this->summary($item))
                ->values()
                ->all(),
        ];
    }

    /** @return array{id: int, name: string, code: string|null, nomenclature_count: int} */
    private function category(CatalogCategoryDTO $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'code' => $category->code,
            'nomenclature_count' => $category->nomenclatureCount,
        ];
    }

    /** @return array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string} */
    private function summary(CatalogNomenclatureSummaryDTO $item): array
    {
        return [
            'part_number' => $item->partNumber,
            'name' => $item->name,
            'category_id' => $item->categoryId,
            'brand_id' => $item->brandId,
            'brand_name' => $item->brandName,
        ];
    }
}
