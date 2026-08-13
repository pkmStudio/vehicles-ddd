<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPaginationMetaDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent adapter CRM read API Warehouse-брендов.
 */
final readonly class BrandCrmRepository implements BrandCrmRepositoryInterface
{
    public function paginate(BrandCrmReadQueryDTO $query): BrandCrmPageDTO
    {
        $builder = $this->baseQuery();

        $this->applyFilters($builder, $query->filters);
        $this->applySearch($builder, $query->search);
        $this->applySort($builder, $query->sort);

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page,
        );

        $items = collect($paginator->items())
            ->map(fn (Brand $brand): BrandCrmListItemDTO => $this->item($brand))
            ->values();

        return new BrandCrmPageDTO(
            data: $items,
            meta: $this->meta($paginator),
        );
    }

    public function findById(int $id): ?BrandCrmListItemDTO
    {
        $brand = $this->baseQuery()
            ->whereKey($id)
            ->first();

        return $brand === null ? null : $this->item($brand);
    }

    private function baseQuery(): Builder
    {
        return Brand::query()->withCount('nomenclatures');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['name']) && trim((string) $filters['name']) !== '') {
            $query->where('brands.name', 'ilike', '%'.trim((string) $filters['name']).'%');
        }

        if (isset($filters['char']) && trim((string) $filters['char']) !== '') {
            $query->where('brands.char', 'ilike', trim((string) $filters['char']));
        }
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('brands.name', 'ilike', "%{$search}%")
                ->orWhere('brands.number_sert', 'ilike', "%{$search}%")
                ->orWhere('brands.char', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query->orWhere('brands.id', (int) $search);
            }
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'brands.id',
            'name' => 'brands.name',
            'number_sert' => 'brands.number_sert',
            'date_start' => 'brands.date_start',
            'date_end' => 'brands.date_end',
            'char' => 'brands.char',
            'nomenclatures_count' => 'nomenclatures_count',
            default => 'brands.id',
        };

        $query->orderBy($column, $direction);
    }

    private function item(Brand $brand): BrandCrmListItemDTO
    {
        return new BrandCrmListItemDTO(
            id: (int) $brand->id,
            name: (string) $brand->name,
            numberSert: (string) $brand->number_sert,
            dateStart: (string) $brand->date_start,
            dateEnd: (string) $brand->date_end,
            char: $brand->char === null ? null : (string) $brand->char,
            nomenclaturesCount: (int) $brand->nomenclatures_count,
            createdAt: $brand->created_at === null ? null : (string) $brand->created_at,
            updatedAt: $brand->updated_at === null ? null : (string) $brand->updated_at,
        );
    }

    private function meta(LengthAwarePaginator $paginator): BrandCrmPaginationMetaDTO
    {
        return new BrandCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}
