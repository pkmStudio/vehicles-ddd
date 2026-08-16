<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\TypeCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\Crm\TypeCrmPaginationMetaDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadFiltersDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Type\TypeCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent adapter CRM read API Warehouse-типов.
 */
final readonly class TypeCrmRepository implements TypeCrmRepositoryInterface
{
    public function paginate(TypeCrmReadQueryDTO $query): TypeCrmPageDTO
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
            ->map(fn (Type $type): TypeCrmListItemDTO => $this->item($type))
            ->values();

        return new TypeCrmPageDTO(
            data: $items,
            meta: $this->meta($paginator),
        );
    }

    public function findById(int $id): ?TypeCrmListItemDTO
    {
        $type = $this->baseQuery()
            ->whereKey($id)
            ->first();

        return $type === null ? null : $this->item($type);
    }

    private function baseQuery(): Builder
    {
        return Type::query()->withCount('nomenclatures');
    }

    private function applyFilters(Builder $query, TypeCrmReadFiltersDTO $filters): void
    {
        if ($filters->name !== null) {
            $query->where('types.name', 'ilike', '%'.$filters->name.'%');
        }

        if ($filters->char !== null) {
            $query->where('types.char', 'ilike', $filters->char);
        }
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null) {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('types.name', 'ilike', "%{$search}%")
                ->orWhere('types.char', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query->orWhere('types.id', (int) $search);
            }
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'types.id',
            'name' => 'types.name',
            'char' => 'types.char',
            'nomenclatures_count' => 'nomenclatures_count',
            default => 'types.id',
        };

        $query->orderBy($column, $direction);
    }

    private function item(Type $type): TypeCrmListItemDTO
    {
        return new TypeCrmListItemDTO(
            id: (int) $type->id,
            name: (string) $type->name,
            char: (string) $type->char,
            nomenclaturesCount: (int) $type->nomenclatures_count,
            createdAt: $type->created_at === null ? null : (string) $type->created_at,
            updatedAt: $type->updated_at === null ? null : (string) $type->updated_at,
        );
    }

    private function meta(LengthAwarePaginator $paginator): TypeCrmPaginationMetaDTO
    {
        return new TypeCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}
