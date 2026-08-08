<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclaturePageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSummaryDTO;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Читает Warehouse-номенклатуру для публичного каталога dan-catalog.
 */
final readonly class NomenclatureCatalogRepository implements NomenclatureCatalogRepositoryInterface
{
    /** @return Collection<int, CatalogCategoryDTO> */
    public function categories(int $brandId): Collection
    {
        return DB::table('types')
            ->join('nomenclatures', function (JoinClause $join) use ($brandId): void {
                $join
                    ->on('nomenclatures.type_id', '=', 'types.id')
                    ->where('nomenclatures.brand_id', $brandId);
            })
            ->groupBy('types.id', 'types.name', 'types.char')
            ->orderBy('types.name')
            ->get([
                'types.id',
                'types.name',
                'types.char',
                DB::raw('COUNT(nomenclatures.id) as nomenclature_count'),
            ])
            ->map(fn (object $category): CatalogCategoryDTO => $this->category($category))
            ->values();
    }

    public function paginateByCategory(int $categoryId, int $brandId, int $page, int $pageSize): ?CatalogNomenclaturePageDTO
    {
        $category = DB::table('types')
            ->leftJoin('nomenclatures', function (JoinClause $join) use ($brandId): void {
                $join
                    ->on('nomenclatures.type_id', '=', 'types.id')
                    ->where('nomenclatures.brand_id', $brandId);
            })
            ->where('types.id', $categoryId)
            ->groupBy('types.id', 'types.name', 'types.char')
            ->first([
                'types.id',
                'types.name',
                'types.char',
                DB::raw('COUNT(nomenclatures.id) as nomenclature_count'),
            ]);

        if ($category === null) {
            return null;
        }

        $total = (int) $category->nomenclature_count;
        $items = $this->summaryQuery($brandId)
            ->where('nomenclatures.type_id', $categoryId)
            ->orderBy('nomenclatures.name')
            ->orderBy('nomenclatures.part_number')
            ->forPage($page, $pageSize)
            ->get()
            ->map(fn (object $nomenclature): CatalogNomenclatureSummaryDTO => $this->summary($nomenclature))
            ->values();

        return new CatalogNomenclaturePageDTO(
            category: $this->category($category),
            items: $items,
            total: $total,
            page: $page,
            pageSize: $pageSize,
            pageCount: (int) ceil($total / $pageSize),
        );
    }

    public function findByPartNumber(string $partNumber, int $brandId): ?CatalogNomenclatureDTO
    {
        $nomenclature = DB::table('nomenclatures')
            ->join('types', 'types.id', '=', 'nomenclatures.type_id')
            ->join('brands', 'brands.id', '=', 'nomenclatures.brand_id')
            ->where('nomenclatures.brand_id', $brandId)
            ->whereRaw('LOWER(nomenclatures.part_number) = ?', [mb_strtolower($partNumber)])
            ->first([
                'nomenclatures.*',
                'types.name as category_name',
                'types.char as category_code',
                'brands.name as brand_name',
                'brands.char as brand_code',
            ]);

        if ($nomenclature === null) {
            return null;
        }

        return new CatalogNomenclatureDTO(
            partNumber: (string) $nomenclature->part_number,
            name: (string) $nomenclature->name,
            categoryId: (int) $nomenclature->type_id,
            categoryName: (string) $nomenclature->category_name,
            categoryCode: $this->nullableString($nomenclature->category_code),
            brandId: (int) $nomenclature->brand_id,
            brandName: (string) $nomenclature->brand_name,
            brandCode: $this->nullableString($nomenclature->brand_code),
            country: (string) $nomenclature->country,
            color: (string) $nomenclature->color,
            weight: (int) $nomenclature->weight,
            material: $this->listStringArray($nomenclature->material),
            vehicleType: $this->listStringArray($nomenclature->vehicle_type),
            quantityPak: (int) $nomenclature->quantity_pak,
            quantityInPak: (int) $nomenclature->quantity_in_pak,
            details: $this->jsonArray($nomenclature->details),
        );
    }

    /** @return Collection<int, CatalogNomenclatureSummaryDTO> */
    public function search(string $query, int $brandId, int $limit): Collection
    {
        $normalizedQuery = mb_strtolower(trim($query));

        return $this->summaryQuery($brandId)
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('nomenclatures.part_number', 'ilike', '%'.trim($query).'%')
                    ->orWhere('nomenclatures.name', 'ilike', '%'.trim($query).'%');
            })
            ->orderByRaw('CASE WHEN LOWER(nomenclatures.part_number) = ? THEN 0 ELSE 1 END', [$normalizedQuery])
            ->orderBy('nomenclatures.name')
            ->orderBy('nomenclatures.part_number')
            ->limit($limit)
            ->get()
            ->map(fn (object $nomenclature): CatalogNomenclatureSummaryDTO => $this->summary($nomenclature))
            ->values();
    }

    private function summaryQuery(int $brandId): Builder
    {
        return DB::table('nomenclatures')
            ->join('brands', 'brands.id', '=', 'nomenclatures.brand_id')
            ->where('nomenclatures.brand_id', $brandId)
            ->select([
                'nomenclatures.part_number',
                'nomenclatures.name',
                'nomenclatures.type_id',
                'nomenclatures.brand_id',
                'brands.name as brand_name',
            ]);
    }

    private function category(object $category): CatalogCategoryDTO
    {
        return new CatalogCategoryDTO(
            id: (int) $category->id,
            name: (string) $category->name,
            code: $this->nullableString($category->char),
            nomenclatureCount: (int) $category->nomenclature_count,
        );
    }

    private function summary(object $nomenclature): CatalogNomenclatureSummaryDTO
    {
        return new CatalogNomenclatureSummaryDTO(
            partNumber: (string) $nomenclature->part_number,
            name: (string) $nomenclature->name,
            categoryId: (int) $nomenclature->type_id,
            brandId: (int) $nomenclature->brand_id,
            brandName: (string) $nomenclature->brand_name,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    /** @return array<string, mixed> */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<int, string> */
    private function listStringArray(mixed $value): array
    {
        return array_values(array_filter(
            $this->jsonArray($value),
            static fn (mixed $item): bool => is_string($item),
        ));
    }
}
