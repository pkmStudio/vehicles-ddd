<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;
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
    /**
     * Возвращает непустые категории выбранного бренда с количеством позиций.
     *
     * @return Collection<int, CatalogCategoryDTO>
     */
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
            ->map(fn (object $category): CatalogCategoryDTO => new CatalogCategoryDTO(
                id: (int) $category->id,
                name: (string) $category->name,
                code: $this->nullableString($category->char),
                nomenclatureCount: (int) $category->nomenclature_count,
            ))
            ->values();
    }

    /**
     * Возвращает категорию с количеством позиций выбранного бренда.
     */
    public function findCategory(int $categoryId, int $brandId): ?CatalogCategoryDTO
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

        return new CatalogCategoryDTO(
            id: (int) $category->id,
            name: (string) $category->name,
            code: $this->nullableString($category->char),
            nomenclatureCount: (int) $category->nomenclature_count,
        );
    }

    /**
     * Возвращает элементы запрошенной страницы категории.
     *
     * @return Collection<int, CatalogNomenclatureSummaryDTO>
     */
    public function findByCategory(int $categoryId, int $brandId, int $page, int $pageSize): Collection
    {
        return $this->summaryQuery($brandId)
            ->where('nomenclatures.type_id', $categoryId)
            ->orderBy('nomenclatures.name')
            ->orderBy('nomenclatures.part_number')
            ->forPage($page, $pageSize)
            ->get()
            ->map(static fn (object $nomenclature): CatalogNomenclatureSummaryDTO => new CatalogNomenclatureSummaryDTO(
                partNumber: (string) $nomenclature->part_number,
                name: (string) $nomenclature->name,
                categoryId: (int) $nomenclature->type_id,
                brandId: (int) $nomenclature->brand_id,
                brandName: (string) $nomenclature->brand_name,
            ))
            ->values();
    }

    /**
     * Возвращает детальную позицию по регистронезависимому артикулу и бренду.
     */
    public function findByPartNumber(string $partNumber, int $brandId): ?CatalogNomenclatureDTO
    {
        $normalizedPartNumber = mb_strtolower($partNumber);
        $nomenclature = DB::table('nomenclatures')
            ->join('types', 'types.id', '=', 'nomenclatures.type_id')
            ->join('brands', 'brands.id', '=', 'nomenclatures.brand_id')
            ->where('nomenclatures.brand_id', $brandId)
            ->whereRaw('LOWER(nomenclatures.part_number) = ?', [$normalizedPartNumber])
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

        $categoryCode = $this->nullableString($nomenclature->category_code);
        $brandCode = $this->nullableString($nomenclature->brand_code);
        $material = $this->listStringArray($nomenclature->material);
        $vehicleType = $this->listStringArray($nomenclature->vehicle_type);
        $details = $this->jsonArray($nomenclature->details);

        return new CatalogNomenclatureDTO(
            partNumber: (string) $nomenclature->part_number,
            name: (string) $nomenclature->name,
            categoryId: (int) $nomenclature->type_id,
            categoryName: (string) $nomenclature->category_name,
            categoryCode: $categoryCode,
            brandId: (int) $nomenclature->brand_id,
            brandName: (string) $nomenclature->brand_name,
            brandCode: $brandCode,
            country: (string) $nomenclature->country,
            color: (string) $nomenclature->color,
            weight: (int) $nomenclature->weight,
            material: $material,
            vehicleType: $vehicleType,
            quantityPak: (int) $nomenclature->quantity_pak,
            quantityInPak: (int) $nomenclature->quantity_in_pak,
            details: $details,
        );
    }

    /**
     * Ищет позиции по артикулу и имени, поднимая точный артикул первым.
     *
     * @return Collection<int, CatalogNomenclatureSummaryDTO>
     */
    public function search(string $query, int $brandId, int $limit): Collection
    {
        $trimmedQuery = trim($query);
        $normalizedQuery = mb_strtolower($trimmedQuery);
        $likeQuery = '%'.$trimmedQuery.'%';

        return $this->summaryQuery($brandId)
            ->where(function (Builder $builder) use ($likeQuery): void {
                $builder
                    ->where('nomenclatures.part_number', 'ilike', $likeQuery)
                    ->orWhere('nomenclatures.name', 'ilike', $likeQuery);
            })
            ->orderByRaw('CASE WHEN LOWER(nomenclatures.part_number) = ? THEN 0 ELSE 1 END', [$normalizedQuery])
            ->orderBy('nomenclatures.name')
            ->orderBy('nomenclatures.part_number')
            ->limit($limit)
            ->get()
            ->map(static fn (object $nomenclature): CatalogNomenclatureSummaryDTO => new CatalogNomenclatureSummaryDTO(
                partNumber: (string) $nomenclature->part_number,
                name: (string) $nomenclature->name,
                categoryId: (int) $nomenclature->type_id,
                brandId: (int) $nomenclature->brand_id,
                brandName: (string) $nomenclature->brand_name,
            ))
            ->values();
    }

    /**
     * Строит общий query краткой позиции для списка и поиска.
     */
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

    /**
     * Нормализует nullable строковое поле read projection.
     */
    private function nullableString(?string $value): ?string
    {
        return $value;
    }

    /**
     * Декодирует JSON-объект details из PostgreSQL/SQLite read projection.
     *
     * @param  array<string, bool|float|int|string|null|array<int|string, bool|float|int|string|null>>|string|null  $value
     * @return array<string, bool|float|int|string|null|array<int|string, bool|float|int|string|null>>
     */
    private function jsonArray(array|string|null $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode(
            json: $value,
            associative: true,
        );

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Декодирует JSON-массив и оставляет только строковые элементы.
     *
     * @param  array<string, bool|float|int|string|null|array<int|string, bool|float|int|string|null>>|string|null  $value
     * @return list<string>
     */
    private function listStringArray(array|string|null $value): array
    {
        $items = [];

        foreach ($this->jsonArray($value) as $item) {
            if (is_string($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
