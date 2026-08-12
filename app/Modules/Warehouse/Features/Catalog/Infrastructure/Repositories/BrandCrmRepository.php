<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\BrandCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\Crm\BrandCrmPaginationMetaDTO;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * SQL adapter CRM read API Warehouse-брендов.
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
            ->map(fn (object $brand): BrandCrmListItemDTO => $this->item($brand))
            ->values();

        return new BrandCrmPageDTO(
            data: $items,
            meta: $this->meta($paginator),
        );
    }

    public function findById(int $id): ?BrandCrmListItemDTO
    {
        $brand = $this->baseQuery()
            ->where('brands.id', $id)
            ->first();

        return $brand === null ? null : $this->item($brand);
    }

    private function baseQuery(): Builder
    {
        return DB::table('brands')
            ->select([
                'brands.id',
                'brands.name',
                'brands.number_sert',
                'brands.date_start',
                'brands.date_end',
                'brands.char',
                'brands.created_at',
                'brands.updated_at',
                DB::raw('(select count(*) from nomenclatures where nomenclatures.brand_id = brands.id) as nomenclatures_count'),
            ]);
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

    private function item(object $row): BrandCrmListItemDTO
    {
        return new BrandCrmListItemDTO(
            id: (int) $row->id,
            name: (string) $row->name,
            numberSert: (string) $row->number_sert,
            dateStart: (string) $row->date_start,
            dateEnd: (string) $row->date_end,
            char: $row->char === null ? null : (string) $row->char,
            nomenclaturesCount: (int) $row->nomenclatures_count,
            createdAt: $row->created_at === null ? null : (string) $row->created_at,
            updatedAt: $row->updated_at === null ? null : (string) $row->updated_at,
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
