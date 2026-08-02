<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmReadRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\VehicleCrmResource;
use PkmStudio\DanWireContracts\Vehicles\Modules\Vehicles\Features\Catalog\Read\DTO\VehicleCrmSearchItem;

/**
 * SQL read adapter for CRM catalog endpoints.
 */
final readonly class VehicleCrmReadRepository implements VehicleCrmReadRepositoryInterface
{
    /**
     * Returns a filtered page of vehicles with pagination metadata.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function paginate(VehicleCrmReadQueryDTO $query): array
    {
        $builder = $this->baseQuery();

        $this->applyFilters($builder, $query->filters);
        $this->applySearch($builder, $query->search);
        $this->applySort($builder, $query->sort);

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page,
        );

        return [
            'data' => collect($paginator->items())
                ->map(fn (object $vehicle): array => $this->resource($vehicle))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * Returns one vehicle with nested CRM details.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $vehicle = $this->baseQuery()
            ->where('vehicles.id', $id)
            ->first();

        if ($vehicle === null) {
            return null;
        }

        return $this->resource($vehicle) + [
            'modifications' => $this->modifications((int) $vehicle->id),
            'part_specifications' => $this->partSpecifications((int) $vehicle->id),
        ];
    }

    /**
     * Returns compact vehicle suggestions for CRM autocomplete.
     *
     * @return list<array<string, mixed>>
     */
    public function search(string $query, int $limit = 20): array
    {
        $builder = $this->baseQuery();
        $this->applySearch($builder, $query);

        return $builder
            ->orderBy('manufacturers.name')
            ->orderBy('vehicles.name')
            ->limit(min(max($limit, 1), 50))
            ->get()
            ->map(fn (object $vehicle): array => $this->searchItem($vehicle))
            ->values()
            ->all();
    }

    /**
     * Returns available vehicle features for CRM filters.
     *
     * @return list<array{id: int, label: string}>
     */
    public function featureOptions(): array
    {
        return DB::table('features')
            ->where('entity_type', 'vehicle')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (object $feature): array => [
                'id' => (int) $feature->id,
                'label' => (string) $feature->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Returns available values for one feature.
     *
     * @return list<array{id: int, feature_id: int, label: string, short_code: string}>
     */
    public function featureValueOptions(int $featureId): array
    {
        if ($featureId <= 0) {
            return [];
        }

        return DB::table('feature_values')
            ->where('feature_id', $featureId)
            ->orderBy('name')
            ->get(['id', 'feature_id', 'name', 'short_code'])
            ->map(fn (object $value): array => [
                'id' => (int) $value->id,
                'feature_id' => (int) $value->feature_id,
                'label' => (string) $value->name,
                'short_code' => (string) $value->short_code,
            ])
            ->values()
            ->all();
    }

    /**
     * Returns supported detail template options for CRM.
     *
     * @return list<array{id: string, label: string}>
     */
    public function detailTemplateOptions(): array
    {
        return [
            ['id' => 'wiper', 'label' => 'Щетки стеклоочистителя'],
        ];
    }

    /**
     * Builds the common vehicle read query with manufacturer and parent fields.
     */
    private function baseQuery(): Builder
    {
        $isChangedColumn = $this->hasIsChangedColumn() ? 'vehicles.is_changed' : DB::raw('false as is_changed');

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
                $isChangedColumn,
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

        foreach (['is_allow', 'is_changed'] as $field) {
            if (! array_key_exists($field, $filters) || $filters[$field] === '') {
                continue;
            }

            if ($field === 'is_changed' && ! $this->hasIsChangedColumn()) {
                if (filter_var($filters[$field], FILTER_VALIDATE_BOOL)) {
                    $query->whereRaw('false');
                }

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
            'is_changed' => $this->hasIsChangedColumn() ? 'vehicles.is_changed' : 'vehicles.id',
            default => 'vehicles.id',
        };

        $query->orderBy($column, $direction);
    }

    /**
     * Converts a raw SQL row to the public CRM vehicle resource.
     *
     * @return array<string, mixed>
     */
    private function resource(object $vehicle): array
    {
        return VehicleCrmResource::fromArray((array) $vehicle)->toArray();
    }

    /**
     * Converts a raw SQL row to the public CRM search item.
     *
     * @return array<string, mixed>
     */
    private function searchItem(object $vehicle): array
    {
        return (new VehicleCrmSearchItem(
            id: (int) $vehicle->id,
            label: $this->fullName($vehicle),
            msId: (int) $vehicle->ms_id,
            manufacturer: isset($vehicle->manufacturer_name) ? (string) $vehicle->manufacturer_name : null,
        ))->toArray();
    }

    private function fullName(object $vehicle): string
    {
        return sprintf(
            '%s | %s %s %s | %s (%s-%s)',
            $vehicle->ms_id,
            $vehicle->manufacturer_name,
            $vehicle->name,
            $vehicle->generation,
            $vehicle->localized_name ?: '',
            $vehicle->generation_year_from,
            $vehicle->generation_year_to ?: 'н.в.',
        );
    }

    /**
     * Loads modifications and attached engines for a CRM vehicle card.
     *
     * @return list<array<string, mixed>>
     */
    private function modifications(int $vehicleId): array
    {
        $modifications = DB::table('modifications')
            ->where('vehicle_id', $vehicleId)
            ->orderBy('year_from')
            ->orderBy('description')
            ->get()
            ->map(fn (object $modification): array => [
                'id' => (int) $modification->id,
                'vehicle_id' => (int) $modification->vehicle_id,
                'ms_id' => (int) $modification->ms_id,
                'mod_id' => (int) $modification->mod_id,
                'year_from' => isset($modification->year_from) ? (int) $modification->year_from : null,
                'year_to' => isset($modification->year_to) ? (int) $modification->year_to : null,
                'description' => isset($modification->description) ? (string) $modification->description : null,
                'type' => (string) $modification->type,
                'brake_system_type' => isset($modification->brake_system_type) ? (string) $modification->brake_system_type : null,
                'power_ps' => isset($modification->power_ps) ? (int) $modification->power_ps : null,
                'power_kw' => isset($modification->power_kw) ? (int) $modification->power_kw : null,
                'engine_type' => isset($modification->engine_type) ? (string) $modification->engine_type : null,
                'gear_type' => isset($modification->gear_type) ? (string) $modification->gear_type : null,
                'drive_type' => isset($modification->drive_type) ? (string) $modification->drive_type : null,
                'localized_name' => isset($modification->localized_name) ? (string) $modification->localized_name : null,
                'number_of_cylinders' => isset($modification->number_of_cylinders) ? (int) $modification->number_of_cylinders : null,
                'capacity_lt' => isset($modification->capacity_lt) ? (float) $modification->capacity_lt : null,
                'engines' => [],
            ])
            ->values();

        if ($modifications->isEmpty()) {
            return [];
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
            ->map(function (array $modification) use ($engines): array {
                $modification['engines'] = collect($engines->get($modification['id'], []))
                    ->map(fn (object $engine): array => [
                        'id' => (int) $engine->id,
                        'eng_id' => (int) $engine->eng_id,
                        'code_engine' => isset($engine->code_engine) ? (string) $engine->code_engine : null,
                        'engine_capacity' => isset($engine->engine_capacity) ? (string) $engine->engine_capacity : null,
                        'cylinder_count' => isset($engine->cylinder_count) ? (int) $engine->cylinder_count : null,
                        'cylinder_diameter' => isset($engine->cylinder_diameter) ? (float) $engine->cylinder_diameter : null,
                        'eng_power_kw_start' => isset($engine->eng_power_kw_start) ? (int) $engine->eng_power_kw_start : null,
                        'eng_power_kw_upto' => isset($engine->eng_power_kw_upto) ? (int) $engine->eng_power_kw_upto : null,
                        'eng_power_ps_start' => isset($engine->eng_power_ps_start) ? (int) $engine->eng_power_ps_start : null,
                        'eng_power_ps_upto' => isset($engine->eng_power_ps_upto) ? (int) $engine->eng_power_ps_upto : null,
                        'eng_number_of_valves' => isset($engine->eng_number_of_valves) ? (int) $engine->eng_number_of_valves : null,
                        'eng_fuel_type' => isset($engine->eng_fuel_type) ? (string) $engine->eng_fuel_type : null,
                        'group_id' => isset($engine->group_id) ? (int) $engine->group_id : null,
                    ])
                    ->values()
                    ->all();

                return $modification;
            })
            ->values()
            ->all();
    }

    /**
     * Loads vehicle part specifications for CRM detail views.
     *
     * @return list<array<string, mixed>>
     */
    private function partSpecifications(int $vehicleId): array
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
            ->map(fn (object $specification): array => [
                'id' => (int) $specification->id,
                'partable_type' => (string) $specification->partable_type,
                'partable_id' => (int) $specification->partable_id,
                'feature_id' => isset($specification->feature_id) ? (int) $specification->feature_id : null,
                'feature_name' => isset($specification->feature_name) ? (string) $specification->feature_name : null,
                'feature_value_id' => isset($specification->feature_value_id) ? (int) $specification->feature_value_id : null,
                'feature_value_name' => isset($specification->feature_value_name) ? (string) $specification->feature_value_name : null,
                'feature_value_short_code' => isset($specification->feature_value_short_code) ? (string) $specification->feature_value_short_code : null,
                'template' => (string) $specification->template,
                'name' => isset($specification->name) ? (string) $specification->name : null,
                'text' => isset($specification->text) ? (string) $specification->text : null,
                'details' => $this->jsonArray($specification->details),
                'created_at' => isset($specification->created_at) ? (string) $specification->created_at : null,
                'updated_at' => isset($specification->updated_at) ? (string) $specification->updated_at : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Decodes nullable JSONB payloads into arrays.
     *
     * @return array<string, mixed>
     */
    private function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Keeps CRM reads compatible with databases that do not have the migration yet.
     */
    private function hasIsChangedColumn(): bool
    {
        return Schema::hasColumn('vehicles', 'is_changed');
    }
}
