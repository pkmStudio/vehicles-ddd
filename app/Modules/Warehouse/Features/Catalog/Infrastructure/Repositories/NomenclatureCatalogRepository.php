<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogCategoryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureSummaryDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use LogicException;

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
        return Type::query()
            ->select(['id', 'name', 'char'])
            ->whereHas(
                'nomenclatures',
                static fn (Builder $query): Builder => $query->where('brand_id', $brandId),
            )
            ->withCount([
                'nomenclatures as nomenclature_count' => static fn (Builder $query): Builder => $query
                    ->where('brand_id', $brandId),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Type $type): CatalogCategoryDTO => $this->category($type))
            ->values();
    }

    /**
     * Возвращает категорию с количеством позиций выбранного бренда.
     */
    public function findCategory(int $categoryId, int $brandId): ?CatalogCategoryDTO
    {
        $category = Type::query()
            ->select(['id', 'name', 'char'])
            ->withCount([
                'nomenclatures as nomenclature_count' => static fn (Builder $query): Builder => $query
                    ->where('brand_id', $brandId),
            ])
            ->find($categoryId);

        if ($category === null) {
            return null;
        }

        return $this->category($category);
    }

    /**
     * Возвращает элементы запрошенной страницы категории.
     *
     * @return Collection<int, CatalogNomenclatureSummaryDTO>
     */
    public function findByCategory(int $categoryId, int $brandId, int $page, int $pageSize): Collection
    {
        return $this->summaryQuery($brandId)
            ->where('type_id', $categoryId)
            ->orderBy('name')
            ->orderBy('part_number')
            ->forPage($page, $pageSize)
            ->get()
            ->map(fn (Nomenclature $nomenclature): CatalogNomenclatureSummaryDTO => $this->summary($nomenclature))
            ->values();
    }

    /**
     * Возвращает детальную позицию по регистронезависимому артикулу и бренду.
     */
    public function findByPartNumber(string $partNumber, int $brandId): ?CatalogNomenclatureDTO
    {
        $normalizedPartNumber = mb_strtolower($partNumber);
        $nomenclature = Nomenclature::query()
            ->with(['type:id,name,char', 'brand:id,name,char'])
            ->whereHas('type')
            ->whereHas('brand')
            ->where('brand_id', $brandId)
            ->whereRaw('LOWER(part_number) = ?', [$normalizedPartNumber])
            ->first();

        if ($nomenclature === null) {
            return null;
        }

        $type = $this->type($nomenclature);
        $brand = $this->brand($nomenclature);
        $categoryCode = $this->nullableString($type->getAttribute('char'));
        $brandCode = $this->nullableString($brand->getAttribute('char'));
        $material = $this->listStringArray($nomenclature->getAttribute('material'));
        $vehicleType = $this->listStringArray($nomenclature->getAttribute('vehicle_type'));
        $details = $this->jsonArray($nomenclature->getAttribute('details'));

        return new CatalogNomenclatureDTO(
            partNumber: (string) $nomenclature->getAttribute('part_number'),
            name: (string) $nomenclature->getAttribute('name'),
            categoryId: (int) $type->getKey(),
            categoryName: (string) $type->getAttribute('name'),
            categoryCode: $categoryCode,
            brandId: (int) $brand->getKey(),
            brandName: (string) $brand->getAttribute('name'),
            brandCode: $brandCode,
            country: (string) $nomenclature->getAttribute('country'),
            color: (string) $nomenclature->getAttribute('color'),
            weight: (int) $nomenclature->getAttribute('weight'),
            material: $material,
            vehicleType: $vehicleType,
            quantityPak: (int) $nomenclature->getAttribute('quantity_pak'),
            quantityInPak: (int) $nomenclature->getAttribute('quantity_in_pak'),
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
                    ->where('part_number', 'ilike', $likeQuery)
                    ->orWhere('name', 'ilike', $likeQuery);
            })
            ->orderByRaw('CASE WHEN LOWER(part_number) = ? THEN 0 ELSE 1 END', [$normalizedQuery])
            ->orderBy('name')
            ->orderBy('part_number')
            ->limit($limit)
            ->get()
            ->map(fn (Nomenclature $nomenclature): CatalogNomenclatureSummaryDTO => $this->summary($nomenclature))
            ->values();
    }

    /**
     * Строит общий query краткой позиции для списка и поиска.
     */
    private function summaryQuery(int $brandId): Builder
    {
        return Nomenclature::query()
            ->with('brand:id,name')
            ->whereHas('brand')
            ->where('brand_id', $brandId)
            ->select(['id', 'part_number', 'name', 'type_id', 'brand_id']);
    }

    /**
     * Мапит Eloquent-модель категории в typed DTO.
     */
    private function category(Type $category): CatalogCategoryDTO
    {
        $code = $this->nullableString($category->getAttribute('char'));

        return new CatalogCategoryDTO(
            id: (int) $category->getKey(),
            name: (string) $category->getAttribute('name'),
            code: $code,
            nomenclatureCount: (int) $category->getAttribute('nomenclature_count'),
        );
    }

    /**
     * Мапит Eloquent-модель номенклатуры в typed DTO.
     */
    private function summary(Nomenclature $nomenclature): CatalogNomenclatureSummaryDTO
    {
        $brand = $this->brand($nomenclature);

        return new CatalogNomenclatureSummaryDTO(
            partNumber: (string) $nomenclature->getAttribute('part_number'),
            name: (string) $nomenclature->getAttribute('name'),
            categoryId: (int) $nomenclature->getAttribute('type_id'),
            brandId: (int) $brand->getKey(),
            brandName: (string) $brand->getAttribute('name'),
        );
    }

    /** Возвращает eager-loaded бренд номенклатуры. */
    private function brand(Nomenclature $nomenclature): Brand
    {
        $brand = $nomenclature->getRelation('brand');

        if (! $brand instanceof Brand) {
            throw new LogicException('Nomenclature brand relation is not loaded.');
        }

        return $brand;
    }

    /** Возвращает eager-loaded тип номенклатуры. */
    private function type(Nomenclature $nomenclature): Type
    {
        $type = $nomenclature->getRelation('type');

        if (! $type instanceof Type) {
            throw new LogicException('Nomenclature type relation is not loaded.');
        }

        return $type;
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
