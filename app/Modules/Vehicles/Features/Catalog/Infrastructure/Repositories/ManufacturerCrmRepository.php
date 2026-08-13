<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPaginationMetaDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerCrmReadQueryDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent adapter CRM read API производителей.
 */
final readonly class ManufacturerCrmRepository implements ManufacturerCrmRepositoryInterface
{
    public function paginate(ManufacturerCrmReadQueryDTO $query): ManufacturerCrmPageDTO
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
            ->map(fn (Manufacturer $manufacturer): ManufacturerCrmListItemDTO => $this->item($manufacturer))
            ->values();

        return new ManufacturerCrmPageDTO(
            data: $items,
            meta: $this->meta($paginator),
        );
    }

    public function findById(int $id): ?ManufacturerCrmListItemDTO
    {
        $manufacturer = $this->baseQuery()
            ->whereKey($id)
            ->first();

        return $manufacturer === null ? null : $this->item($manufacturer);
    }

    private function baseQuery(): Builder
    {
        return Manufacturer::query()->withCount('vehicles');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['provider']) && trim((string) $filters['provider']) !== '') {
            $query->where('manufacturers.provider', trim((string) $filters['provider']));
        }

        if (isset($filters['name']) && trim((string) $filters['name']) !== '') {
            $query->where('manufacturers.name', 'ilike', '%'.trim((string) $filters['name']).'%');
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
                ->where('manufacturers.name', 'ilike', "%{$search}%")
                ->orWhere('manufacturers.provider', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query
                    ->orWhere('manufacturers.id', (int) $search)
                    ->orWhere('manufacturers.mfa_id', (int) $search);
            }
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'manufacturers.id',
            'mfa_id' => 'manufacturers.mfa_id',
            'name' => 'manufacturers.name',
            'provider' => 'manufacturers.provider',
            'vehicles_count' => 'vehicles_count',
            default => 'manufacturers.id',
        };

        $query->orderBy($column, $direction);
    }

    private function item(Manufacturer $manufacturer): ManufacturerCrmListItemDTO
    {
        return new ManufacturerCrmListItemDTO(
            id: (int) $manufacturer->id,
            mfaId: (int) $manufacturer->mfa_id,
            name: (string) $manufacturer->name,
            provider: $manufacturer->provider->value,
            vehiclesCount: (int) $manufacturer->vehicles_count,
        );
    }

    private function meta(LengthAwarePaginator $paginator): ManufacturerCrmPaginationMetaDTO
    {
        return new ManufacturerCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}
