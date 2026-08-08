<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmSearchItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories\NomenclatureCrmBrandOptionDTOFactory;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories\NomenclatureCrmListItemDTOFactory;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories\NomenclatureCrmPageDTOFactory;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories\NomenclatureCrmSearchItemDTOFactory;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories\NomenclatureCrm\Factories\NomenclatureCrmTypeOptionDTOFactory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SQL read adapter for CRM Warehouse nomenclature endpoints.
 */
final readonly class NomenclatureCrmRepository implements NomenclatureCrmRepositoryInterface
{
    private const int OPTION_LIMIT = 1000;

    public function __construct(
        private NomenclatureCrmListItemDTOFactory $listItemFactory,
        private NomenclatureCrmPageDTOFactory $pageFactory,
        private NomenclatureCrmSearchItemDTOFactory $searchItemFactory,
        private NomenclatureCrmTypeOptionDTOFactory $typeOptionFactory,
        private NomenclatureCrmBrandOptionDTOFactory $brandOptionFactory,
    ) {}

    public function paginate(NomenclatureCrmReadQueryDTO $query): NomenclatureCrmPageDTO
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
            ->map(fn (object $nomenclature): NomenclatureCrmListItemDTO => $this->listItemFactory->make($nomenclature))
            ->values();

        return $this->pageFactory->make($items, $paginator);
    }

    public function findById(int $id): ?NomenclatureCrmListItemDTO
    {
        $nomenclature = $this->baseQuery()
            ->where('nomenclatures.id', $id)
            ->first();

        return $nomenclature === null ? null : $this->listItemFactory->make($nomenclature);
    }

    /**
     * @return Collection<int, NomenclatureCrmSearchItemDTO>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        $builder = $this->baseQuery();
        $this->applySearch($builder, $query);

        return $builder
            ->orderBy('brands.name')
            ->orderBy('nomenclatures.part_number')
            ->limit(min(max($limit, 1), 50))
            ->get()
            ->map(fn (object $nomenclature): NomenclatureCrmSearchItemDTO => $this->searchItemFactory->make($nomenclature))
            ->values();
    }

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function typeOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = DB::table('types')
            ->select(['id', 'name', 'char'])
            ->orderBy('id');

        if ($id !== null) {
            $builder->where('id', $id);
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
            ->limit(min(max($limit, 1), self::OPTION_LIMIT))
            ->get()
            ->map(fn (object $type): NomenclatureCrmOptionDTO => $this->typeOptionFactory->make($type))
            ->values();
    }

    /**
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function brandOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = DB::table('brands')
            ->select(['id', 'name', 'char'])
            ->orderBy('name');

        if ($id !== null) {
            $builder->where('id', $id);
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
            ->limit(min(max($limit, 1), self::OPTION_LIMIT))
            ->get()
            ->map(fn (object $brand): NomenclatureCrmOptionDTO => $this->brandOptionFactory->make($brand))
            ->values();
    }

    private function baseQuery(): Builder
    {
        return DB::table('nomenclatures')
            ->leftJoin('types', 'types.id', '=', 'nomenclatures.type_id')
            ->leftJoin('brands', 'brands.id', '=', 'nomenclatures.brand_id')
            ->select([
                'nomenclatures.id',
                'nomenclatures.type_id',
                'types.name as type_name',
                'types.char as type_char',
                'nomenclatures.brand_id',
                'brands.name as brand_name',
                'brands.char as brand_char',
                'nomenclatures.name',
                'nomenclatures.country',
                'nomenclatures.part_number',
                'nomenclatures.color',
                'nomenclatures.weight',
                'nomenclatures.material',
                'nomenclatures.vehicle_type',
                'nomenclatures.quantity_pak',
                'nomenclatures.quantity_in_pak',
                'nomenclatures.details',
                'nomenclatures.created_at',
                'nomenclatures.updated_at',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['type_id', 'brand_id'] as $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn("nomenclatures.{$field}", $values);
        }

        foreach (['name', 'country', 'part_number'] as $field) {
            if (! isset($filters[$field]) || trim((string) $filters[$field]) === '') {
                continue;
            }

            $query->where("nomenclatures.{$field}", 'ilike', '%'.trim((string) $filters[$field]).'%');
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
                ->where('nomenclatures.name', 'ilike', "%{$search}%")
                ->orWhere('nomenclatures.part_number', 'ilike', "%{$search}%")
                ->orWhere('nomenclatures.country', 'ilike', "%{$search}%")
                ->orWhere('types.name', 'ilike', "%{$search}%")
                ->orWhere('brands.name', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query->orWhere('nomenclatures.id', (int) $search);
            }
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'nomenclatures.id',
            'name' => 'nomenclatures.name',
            'country' => 'nomenclatures.country',
            'part_number' => 'nomenclatures.part_number',
            'type_name' => 'types.name',
            'brand_name' => 'brands.name',
            'weight' => 'nomenclatures.weight',
            'quantity_pak' => 'nomenclatures.quantity_pak',
            'quantity_in_pak' => 'nomenclatures.quantity_in_pak',
            default => 'nomenclatures.id',
        };

        $query->orderBy($column, $direction);
    }
}
