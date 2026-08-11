<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmEngineDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPartSpecificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories\VehicleCrmDetailDTOFactory;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories\VehicleCrmModificationDTOFactory;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories\VehicleCrmPageDTOFactory;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories\VehicleCrmPartSpecificationDTOFactory;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories\VehicleCrmSearchItemDTOFactory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SQL read adapter for CRM catalog endpoints.
 */
final readonly class VehicleCrmRepository implements VehicleCrmRepositoryInterface
{
    public function __construct(
        private VehicleCrmSearchItemDTOFactory $searchItemFactory,
        private VehicleCrmPageDTOFactory $pageFactory,
        private VehicleCrmDetailDTOFactory $detailFactory,
        private VehicleCrmModificationDTOFactory $modificationFactory,
        private VehicleCrmPartSpecificationDTOFactory $partSpecificationFactory,
    ) {}

    /**
     * Returns a filtered page of vehicles with pagination metadata as local DTO.
     */
    public function paginate(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO
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
            ->map(fn (object $vehicle): VehicleCrmListItemDTO => VehicleCrmListItemDTO::fromArray((array) $vehicle))
            ->values();

        return $this->pageFactory->make($items, $paginator);
    }

    /**
     * Returns one vehicle with nested CRM details as local DTO.
     */
    public function findById(int $id): ?VehicleCrmDetailDTO
    {
        $vehicle = $this->baseQuery()
            ->where('vehicles.id', $id)
            ->first();

        if ($vehicle === null) {
            return null;
        }

        return $this->detailFactory->make(
            vehicle: VehicleCrmListItemDTO::fromArray((array) $vehicle),
            modifications: $this->modifications((int) $vehicle->id),
            partSpecifications: $this->partSpecifications((int) $vehicle->id),
        );
    }

    /**
     * Returns compact vehicle suggestions for CRM autocomplete.
     *
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        $builder = $this->baseQuery();
        $this->applySearch($builder, $query);

        return $builder
            ->orderBy('manufacturers.name')
            ->orderBy('vehicles.name')
            ->limit(min(max($limit, 1), 50))
            ->get()
            ->map(fn (object $vehicle): VehicleCrmSearchItemDTO => $this->searchItemFactory->make($vehicle))
            ->values();
    }

    /**
     * Returns available vehicle features for CRM filters.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function featureOptions(): Collection
    {
        return DB::table('features')
            ->where('entity_type', 'vehicle')
            ->orderBy('name')
            ->get(['id', 'name as label'])
            ->map(fn (object $feature): VehicleCrmFeatureOptionDTO => VehicleCrmFeatureOptionDTO::fromArray((array) $feature))
            ->values();
    }

    /**
     * Returns available values for one feature.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValueOptions(int $featureId): Collection
    {
        if ($featureId <= 0) {
            return collect();
        }

        return DB::table('feature_values')
            ->where('feature_id', $featureId)
            ->orderBy('name')
            ->get(['id', 'feature_id', 'name as label', 'short_code'])
            ->map(fn (object $value): VehicleCrmFeatureValueOptionDTO => VehicleCrmFeatureValueOptionDTO::fromArray((array) $value))
            ->values();
    }

    /**
     * Returns manufacturer options for CRM forms.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturerOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = DB::table('manufacturers')
            ->select(['id', 'mfa_id', 'name as label'])
            ->orderBy('name');

        if ($id !== null) {
            $builder->where('id', $id);
        } elseif ($query !== null && trim($query) !== '') {
            $search = trim($query);

            $builder->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'ilike', "%{$search}%");

                if (is_numeric($search)) {
                    $builder->orWhere('mfa_id', (int) $search);
                }
            });
        }

        return $builder
            ->limit(min(max($limit, 1), 50))
            ->get()
            ->map(fn (object $manufacturer): VehicleCrmManufacturerOptionDTO => VehicleCrmManufacturerOptionDTO::fromArray((array) $manufacturer))
            ->values();
    }

    /**
     * Builds the common vehicle read query with manufacturer and parent fields.
     */
    private function baseQuery(): Builder
    {
        return DB::table('vehicles')
            ->leftJoin('vehicles as parent_vehicles', 'parent_vehicles.id', '=', 'vehicles.parent_id')
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'vehicles.manufacturer_id')
            ->select([
                'vehicles.id',
                'vehicles.parent_id',
                'parent_vehicles.ms_id as parent_ms_id',
                'vehicles.manufacturer_id',
                'manufacturers.name as manufacturer_name',
                'vehicles.mfa_id',
                'vehicles.ms_id',
                'vehicles.name',
                'vehicles.localized_name',
                'vehicles.excel_table_id',
                'vehicles.generation',
                'vehicles.generation_short',
                'vehicles.generation_year_from',
                'vehicles.generation_year_to',
                'vehicles.type',
                'vehicles.type_carcase',
                'vehicles.provider',
                'vehicles.steering_type',
                'vehicles.is_allow',
            ]);
    }

    /**
     * Applies CRM filters to the base vehicle query.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['manufacturer_id', 'type_carcase', 'provider'] as $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn("vehicles.{$field}", $values);
        }

        foreach (['name' => 'vehicles.name', 'manufacturer_name' => 'manufacturers.name'] as $field => $column) {
            if (! isset($filters[$field]) || trim((string) $filters[$field]) === '') {
                continue;
            }

            $query->where($column, 'ilike', '%'.trim((string) $filters[$field]).'%');
        }

        foreach (['is_allow'] as $field) {
            if (! array_key_exists($field, $filters) || $filters[$field] === '') {
                continue;
            }

            $query->where("vehicles.{$field}", filter_var($filters[$field], FILTER_VALIDATE_BOOL));
        }
    }

    /**
     * Applies a full-text-like search over vehicle and manufacturer columns.
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('vehicles.name', 'ilike', "%{$search}%")
                ->orWhere('manufacturers.name', 'ilike', "%{$search}%")
                ->orWhere('vehicles.generation', 'ilike', "%{$search}%")
                ->orWhere('vehicles.localized_name', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query->orWhere('vehicles.ms_id', (int) $search);
            }
        });
    }

    /**
     * Applies a whitelisted CRM sort expression.
     */
    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'vehicles.id',
            'ms_id' => 'vehicles.ms_id',
            'mfa_id' => 'vehicles.mfa_id',
            'manufacturer_name' => 'manufacturers.name',
            'name' => 'vehicles.name',
            'generation_year_from' => 'vehicles.generation_year_from',
            'generation_year_to' => 'vehicles.generation_year_to',
            'type_carcase' => 'vehicles.type_carcase',
            'provider' => 'vehicles.provider',
            'is_allow' => 'vehicles.is_allow',
            default => 'vehicles.id',
        };

        $query->orderBy($column, $direction);
    }

    /**
     * Loads modifications and attached engines for a CRM vehicle card.
     *
     * @return Collection<int, VehicleCrmModificationDTO>
     */
    private function modifications(int $vehicleId): Collection
    {
        $modifications = DB::table('modifications')
            ->where('vehicle_id', $vehicleId)
            ->orderBy('year_from')
            ->orderBy('description')
            ->get()
            ->values();

        if ($modifications->isEmpty()) {
            return collect();
        }

        $engines = DB::table('engine_modification')
            ->join('engines', 'engines.id', '=', 'engine_modification.engine_id')
            ->whereIn('engine_modification.modification_id', $modifications->pluck('id')->all())
            ->orderBy('engines.code_engine')
            ->get([
                'engine_modification.modification_id',
                'engines.id',
                'engines.eng_id',
                'engines.code_engine',
                'engines.engine_capacity',
                'engines.cylinder_count',
                'engines.cylinder_diameter',
                'engines.eng_power_kw_start',
                'engines.eng_power_kw_upto',
                'engines.eng_power_ps_start',
                'engines.eng_power_ps_upto',
                'engines.eng_number_of_valves',
                'engines.eng_fuel_type',
                'engines.group_id',
            ])
            ->groupBy('modification_id');

        return $modifications
            ->map(function (object $modification) use ($engines): VehicleCrmModificationDTO {
                $modificationEngines = collect($engines->get($modification->id, []))
                    ->map(fn (object $engine): VehicleCrmEngineDTO => VehicleCrmEngineDTO::fromArray((array) $engine))
                    ->values();

                return $this->modificationFactory->make($modification, $modificationEngines);
            })
            ->values();
    }

    /**
     * Loads vehicle part specifications for CRM detail views.
     *
     * @return Collection<int, VehicleCrmPartSpecificationDTO>
     */
    private function partSpecifications(int $vehicleId): Collection
    {
        return DB::table('part_specifications')
            ->leftJoin('feature_values', 'feature_values.id', '=', 'part_specifications.feature_value_id')
            ->leftJoin('features', 'features.id', '=', 'feature_values.feature_id')
            ->where('part_specifications.partable_type', 'vehicle')
            ->where('part_specifications.partable_id', $vehicleId)
            ->orderBy('part_specifications.template')
            ->orderBy('part_specifications.id')
            ->get([
                'part_specifications.id',
                'part_specifications.partable_type',
                'part_specifications.partable_id',
                'part_specifications.feature_value_id',
                'features.id as feature_id',
                'features.name as feature_name',
                'feature_values.name as feature_value_name',
                'feature_values.short_code as feature_value_short_code',
                'part_specifications.template',
                'part_specifications.name',
                'part_specifications.text',
                'part_specifications.details',
                'part_specifications.created_at',
                'part_specifications.updated_at',
            ])
            ->map(fn (object $specification): VehicleCrmPartSpecificationDTO => $this->partSpecificationFactory->make($specification))
            ->values();
    }
}
