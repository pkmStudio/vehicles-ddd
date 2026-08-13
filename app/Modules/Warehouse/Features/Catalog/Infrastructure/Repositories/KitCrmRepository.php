<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPaginationMetaDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Eloquent adapter CRM read API комплектов Warehouse.
 */
final readonly class KitCrmRepository implements KitCrmRepositoryInterface
{
    private const int OPTION_LIMIT = 1000;

    /**
     * Читает постраничный список комплектов для CRM.
     */
    public function paginate(KitCrmReadQueryDTO $query): KitCrmPageDTO
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
            ->map(fn (Kit $kit): KitCrmListItemDTO => $this->item($kit))
            ->values();

        return new KitCrmPageDTO(
            data: $items,
            meta: $this->meta($paginator),
        );
    }

    /**
     * Читает detail-снимок комплекта по id.
     */
    public function findById(int $id): ?KitCrmListItemDTO
    {
        $kit = $this->baseQuery()
            ->whereKey($id)
            ->first();

        return $kit === null ? null : $this->item($kit, withNomenclatures: true);
    }

    /**
     * Читает nomenclature options для CRM-формы комплекта.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function nomenclatureOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = Nomenclature::query()
            ->select('nomenclatures.*')
            ->with('brand')
            ->leftJoin('brands', 'brands.id', '=', 'nomenclatures.brand_id')
            ->orderBy('brands.name')
            ->orderBy('nomenclatures.part_number');

        if ($id !== null) {
            $builder->whereKey($id);
        } elseif ($query !== null && trim($query) !== '') {
            $search = trim($query);
            $builder->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('nomenclatures.part_number', 'ilike', "%{$search}%")
                    ->orWhere('nomenclatures.name', 'ilike', "%{$search}%")
                    ->orWhere('brands.name', 'ilike', "%{$search}%");

                if (is_numeric($search)) {
                    $builder->orWhere('nomenclatures.id', (int) $search);
                }
            });
        }

        return $builder
            ->limit($this->optionLimit($limit))
            ->get()
            ->map(fn (Nomenclature $nomenclature): KitCrmOptionDTO => new KitCrmOptionDTO(
                id: (int) $nomenclature->id,
                label: trim("[{$nomenclature->part_number}] {$nomenclature->name}"),
                meta: [
                    'part_number' => (string) $nomenclature->part_number,
                    'brand_name' => $nomenclature->brand?->name === null ? null : (string) $nomenclature->brand->name,
                ],
            ))
            ->values();
    }

    /**
     * Читает pack dimension options для CRM-формы комплекта.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function packDimensionOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = PackDimension::query()
            ->with('type')
            ->orderBy('pack_dimensions.name');

        if ($id !== null) {
            $builder->whereKey($id);
        } elseif ($query !== null && trim($query) !== '') {
            $search = trim($query);
            $builder->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('pack_dimensions.name', 'ilike', "%{$search}%")
                    ->orWhereHas('type', function (Builder $type) use ($search): void {
                        $type->where('types.name', 'ilike', "%{$search}%");
                    });

                if (is_numeric($search)) {
                    $builder->orWhere('pack_dimensions.id', (int) $search);
                }
            });
        }

        return $builder
            ->limit($this->optionLimit($limit))
            ->get()
            ->map(fn (PackDimension $packDimension): KitCrmOptionDTO => new KitCrmOptionDTO(
                id: (int) $packDimension->id,
                label: (string) $packDimension->name,
                meta: [
                    'type_name' => $packDimension->type?->name === null ? null : (string) $packDimension->type->name,
                    'weight' => (int) $packDimension->weight,
                    'dimensions' => "{$packDimension->width} x {$packDimension->height} x {$packDimension->length}",
                ],
            ))
            ->values();
    }

    /**
     * Читает type options для CRM-формы комплекта.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function typeOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = Type::query()->orderBy('id');

        if ($id !== null) {
            $builder->whereKey($id);
        } elseif ($query !== null && trim($query) !== '') {
            $search = trim($query);
            $builder->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('char', 'ilike', "%{$search}%");

                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        return $builder
            ->limit($this->optionLimit($limit))
            ->get()
            ->map(fn (Type $type): KitCrmOptionDTO => new KitCrmOptionDTO(
                id: (int) $type->id,
                label: (string) $type->name,
                meta: ['char' => $type->char === null ? null : (string) $type->char],
            ))
            ->values();
    }

    /**
     * Собирает базовый query builder комплектов для CRM read API.
     */
    private function baseQuery(): Builder
    {
        return Kit::query()
            ->select('kits.*')
            ->with(['type', 'packDimension', 'nomenclatures'])
            ->withCount('nomenclatures');
    }

    /**
     * Применяет фильтры CRM списка комплектов.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['type_id', 'pack_dimension_id'] as $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn("kits.{$field}", $values);
        }

        if (isset($filters['complectation']) && trim((string) $filters['complectation']) !== '') {
            $query->where('kits.complectation', 'ilike', '%'.trim((string) $filters['complectation']).'%');
        }
    }

    /**
     * Применяет полнотекстовый search к CRM списку комплектов.
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('kits.complectation', 'ilike', "%{$search}%")
                ->orWhereHas('type', function (Builder $type) use ($search): void {
                    $type->where('types.name', 'ilike', "%{$search}%");
                })
                ->orWhereHas('packDimension', function (Builder $packDimension) use ($search): void {
                    $packDimension->where('pack_dimensions.name', 'ilike', "%{$search}%");
                })
                ->orWhereHas('nomenclatures', function (Builder $nomenclatures) use ($search): void {
                    $nomenclatures
                        ->where('nomenclatures.part_number', 'ilike', "%{$search}%")
                        ->orWhere('nomenclatures.name', 'ilike', "%{$search}%");
                });

            if (is_numeric($search)) {
                $query->orWhere('kits.id', (int) $search);
            }
        });
    }

    /**
     * Применяет сортировку CRM списка комплектов.
     */
    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if ($field === 'type_name') {
            $query
                ->leftJoin('types', 'types.id', '=', 'kits.type_id')
                ->select('kits.*')
                ->orderBy('types.name', $direction);

            return;
        }

        if ($field === 'pack_dimension_name') {
            $query
                ->leftJoin('pack_dimensions', 'pack_dimensions.id', '=', 'kits.pack_dimension_id')
                ->select('kits.*')
                ->orderBy('pack_dimensions.name', $direction);

            return;
        }

        $column = match ($field) {
            'id' => 'kits.id',
            'complectation' => 'kits.complectation',
            'weight' => 'kits.weight',
            'guarantee' => 'kits.guarantee',
            default => 'kits.id',
        };

        $query->orderBy($column, $direction);
    }

    /**
     * Маппит комплект в CRM DTO.
     */
    private function item(Kit $kit, bool $withNomenclatures = false): KitCrmListItemDTO
    {
        return new KitCrmListItemDTO(
            id: (int) $kit->id,
            complectation: (string) $kit->complectation,
            guarantee: (int) $kit->guarantee,
            quantityInPackage: (int) $kit->quantity_in_package,
            quantityPackage: (int) $kit->quantity_package,
            complement: (bool) $kit->complement,
            weight: (int) $kit->weight,
            packDimensionId: (int) $kit->pack_dimension_id,
            packDimensionName: $kit->packDimension?->name === null ? null : (string) $kit->packDimension->name,
            typeId: (int) $kit->type_id,
            typeName: $kit->type?->name === null ? null : (string) $kit->type->name,
            typeChar: $kit->type?->char === null ? null : (string) $kit->type->char,
            importHash: $kit->import_hash === null ? null : (string) $kit->import_hash,
            isSaleSeparately: (bool) $kit->is_sale_separately,
            isActive: (bool) $kit->is_active,
            nomenclaturesCount: (int) $kit->nomenclatures_count,
            nomenclaturesList: $kit->nomenclatures
                ->pluck('part_number')
                ->implode(', '),
            nomenclatures: $withNomenclatures ? $this->nomenclatures($kit) : [],
            createdAt: $kit->created_at === null ? null : (string) $kit->created_at,
            updatedAt: $kit->updated_at === null ? null : (string) $kit->updated_at,
        );
    }

    /**
     * Возвращает detail-список номенклатур комплекта.
     *
     * @return list<array{id: int, label: string, part_number: string}>
     */
    private function nomenclatures(Kit $kit): array
    {
        return $kit->nomenclatures
            ->map(fn (Nomenclature $nomenclature): array => [
                'id' => (int) $nomenclature->id,
                'label' => trim("[{$nomenclature->part_number}] {$nomenclature->name}"),
                'part_number' => (string) $nomenclature->part_number,
            ])
            ->values()
            ->all();
    }

    /**
     * Маппит Laravel paginator в CRM DTO метаданных пагинации.
     */
    private function meta(LengthAwarePaginator $paginator): KitCrmPaginationMetaDTO
    {
        return new KitCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }

    /**
     * Нормализует лимит options endpoint-а.
     */
    private function optionLimit(int $limit): int
    {
        return min(max($limit, 1), self::OPTION_LIMIT);
    }
}
