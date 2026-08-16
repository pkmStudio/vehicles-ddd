<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Presentation\Http\Presenters;

use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicabilityCheckResultDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicabilityEvidenceDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableCategoryDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableNomenclatureDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableNomenclaturePageDTO;
use Illuminate\Support\Collection;

/** Собирает HTTP shape публичной применяемости каталога. */
final readonly class CatalogApplicabilityPresenter
{
    /** @return array{part_number: string, modification_id: int, status: string, evidence: list<array{kit_id: int, target_type: string, source: string, algorithm: string|null}>} */
    public function check(ApplicabilityCheckResultDTO $result): array
    {
        return [
            'part_number' => $result->partNumber,
            'modification_id' => $result->modificationId,
            'status' => $result->status->value,
            'evidence' => $result->evidence
                ->map(fn (ApplicabilityEvidenceDTO $evidence): array => $this->evidence($evidence))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, ApplicableCategoryDTO>  $categories
     * @return list<array{id: int, name: string, code: string|null, nomenclature_count: int}>
     */
    public function categories(Collection $categories): array
    {
        return $categories
            ->map(fn (ApplicableCategoryDTO $category): array => $this->category($category))
            ->values()
            ->all();
    }

    /**
     * @return array{category: array{id: int, name: string, code: string|null, nomenclature_count: int}, items: list<array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string}>, total: int, page: int, page_size: int, page_count: int}
     */
    public function page(ApplicableNomenclaturePageDTO $page): array
    {
        return [
            'category' => $this->category($page->category),
            'items' => $page->items
                ->map(fn (ApplicableNomenclatureDTO $item): array => $this->nomenclature($item))
                ->values()
                ->all(),
            'total' => $page->total,
            'page' => $page->page,
            'page_size' => $page->pageSize,
            'page_count' => $page->pageCount,
        ];
    }

    /** @return array{kit_id: int, target_type: string, source: string, algorithm: string|null} */
    private function evidence(ApplicabilityEvidenceDTO $evidence): array
    {
        return [
            'kit_id' => $evidence->kitId,
            'target_type' => $evidence->targetType,
            'source' => $evidence->source,
            'algorithm' => $evidence->algorithm,
        ];
    }

    /** @return array{id: int, name: string, code: string|null, nomenclature_count: int} */
    private function category(ApplicableCategoryDTO $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'code' => $category->code,
            'nomenclature_count' => $category->nomenclatureCount,
        ];
    }

    /** @return array{part_number: string, name: string, category_id: int, brand_id: int, brand_name: string} */
    private function nomenclature(ApplicableNomenclatureDTO $item): array
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
