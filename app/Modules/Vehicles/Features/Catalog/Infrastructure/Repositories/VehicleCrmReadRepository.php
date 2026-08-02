<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmReadRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmEngineDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPaginationMetaDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPartSpecificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SQL read adapter for CRM catalog endpoints.
 */
final readonly class VehicleCrmReadRepository implements VehicleCrmReadRepositoryInterface
{
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
            ->map(fn (object $vehicle): VehicleCrmListItemDTO => $this->listItem($vehicle))
            ->values();

        $meta = new VehicleCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );

        return new VehicleCrmPageDTO(
            data: $items,
            meta: $meta,
        );
    }

    /**
     * Returns one vehicle with nested CRM details as local DTO.
     */
    public function find(int $id): ?VehicleCrmDetailDTO
    {
        $vehicle = $this->baseQuery()
            ->where('vehicles.id', $id)
            ->first();

        if ($vehicle === null) {
            return null;
        }

        return new VehicleCrmDetailDTO(
            vehicle: $this->listItem($vehicle),
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
            ->map(fn (object $vehicle): VehicleCrmSearchItemDTO => $this->searchItem($vehicle))
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
            ->get(['id', 'name'])
            ->map(fn (object $feature): VehicleCrmFeatureOptionDTO => new VehicleCrmFeatureOptionDTO(
                id: (int) $feature->id,
                label: (string) $feature->name,
            ))
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
            ->get(['id', 'feature_id', 'name', 'short_code'])
            ->map(fn (object $value): VehicleCrmFeatureValueOptionDTO => new VehicleCrmFeatureValueOptionDTO(
                id: (int) $value->id,
                featureId: (int) $value->feature_id,
                label: (string) $value->name,
                shortCode: (string) $value->short_code,
            ))
            ->values();
    }

    /**
     * Returns supported detail template options for CRM.
     *
     * @return Collection<int, VehicleCrmDetailTemplateOptionDTO>
     */
    public function detailTemplateOptions(): Collection
    {
        return collect([
            new VehicleCrmDetailTemplateOptionDTO(
                id: 'wiper',
                label: 'Щетки стеклоочистителя',
            ),
        ]);
    }

    /**
     * Returns manufacturer options for CRM forms.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturerOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = DB::table('manufacturers')
            ->select(['id', 'mfa_id', 'name'])
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
            ->map(fn (object $manufacturer): VehicleCrmManufacturerOptionDTO => new VehicleCrmManufacturerOptionDTO(
                id: (int) $manufacturer->id,
                mfaId: (int) $manufacturer->mfa_id,
                label: (string) $manufacturer->name,
            ))
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
     * Converts a raw SQL row to a local CRM vehicle projection.
     */
    private function listItem(object $vehicle): VehicleCrmListItemDTO
    {
        return new VehicleCrmListItemDTO(
            id: (int) $vehicle->id,
            parentId: isset($vehicle->parent_id) ? (int) $vehicle->parent_id : null,
            parentMsId: isset($vehicle->parent_ms_id) ? (int) $vehicle->parent_ms_id : null,
            manufacturerId: (int) $vehicle->manufacturer_id,
            manufacturerName: isset($vehicle->manufacturer_name) ? (string) $vehicle->manufacturer_name : null,
            mfaId: (int) $vehicle->mfa_id,
            msId: (int) $vehicle->ms_id,
            name: (string) $vehicle->name,
            localizedName: isset($vehicle->localized_name) ? (string) $vehicle->localized_name : null,
            excelTableId: isset($vehicle->excel_table_id) ? (string) $vehicle->excel_table_id : null,
            generation: isset($vehicle->generation) ? (string) $vehicle->generation : null,
            generationShort: isset($vehicle->generation_short) ? (string) $vehicle->generation_short : null,
            generationYearFrom: isset($vehicle->generation_year_from) ? (int) $vehicle->generation_year_from : null,
            generationYearTo: isset($vehicle->generation_year_to) ? (int) $vehicle->generation_year_to : null,
            type: (string) $vehicle->type,
            typeCarcase: (string) $vehicle->type_carcase,
            provider: (string) $vehicle->provider,
            steeringType: (string) $vehicle->steering_type,
            isAllow: (bool) $vehicle->is_allow,
        );
    }

    /**
     * Converts a raw SQL row to a local CRM search projection.
     */
    private function searchItem(object $vehicle): VehicleCrmSearchItemDTO
    {
        return new VehicleCrmSearchItemDTO(
            id: (int) $vehicle->id,
            label: $this->fullName($vehicle),
            msId: (int) $vehicle->ms_id,
            manufacturer: isset($vehicle->manufacturer_name) ? (string) $vehicle->manufacturer_name : null,
        );
    }

    /**
     * Builds the human-readable CRM search label for one vehicle row.
     */
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
     * @return Collection<int, VehicleCrmModificationDTO>
     */
    private function modifications(int $vehicleId): Collection
    {
        $modifications = DB::table('modifications')
            ->where('vehicle_id', $vehicleId)
            ->orderBy('year_from')
            ->orderBy('description')
            ->get()
            ->map(fn (object $modification): array => $this->modificationRow($modification))
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
            ->map(function (array $modification) use ($engines): VehicleCrmModificationDTO {
                $modificationEngines = collect($engines->get($modification['id'], []))
                    ->map(fn (object $engine): VehicleCrmEngineDTO => $this->engine($engine))
                    ->values();

                return new VehicleCrmModificationDTO(
                    id: $modification['id'],
                    vehicleId: $modification['vehicle_id'],
                    msId: $modification['ms_id'],
                    modId: $modification['mod_id'],
                    yearFrom: $modification['year_from'],
                    yearTo: $modification['year_to'],
                    description: $modification['description'],
                    type: $modification['type'],
                    brakeSystemType: $modification['brake_system_type'],
                    powerPs: $modification['power_ps'],
                    powerKw: $modification['power_kw'],
                    engineType: $modification['engine_type'],
                    gearType: $modification['gear_type'],
                    driveType: $modification['drive_type'],
                    localizedName: $modification['localized_name'],
                    numberOfCylinders: $modification['number_of_cylinders'],
                    capacityLt: $modification['capacity_lt'],
                    engines: $modificationEngines,
                );
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
            ->map(fn (object $specification): VehicleCrmPartSpecificationDTO => new VehicleCrmPartSpecificationDTO(
                id: (int) $specification->id,
                partableType: (string) $specification->partable_type,
                partableId: (int) $specification->partable_id,
                featureId: isset($specification->feature_id) ? (int) $specification->feature_id : null,
                featureName: isset($specification->feature_name) ? (string) $specification->feature_name : null,
                featureValueId: isset($specification->feature_value_id) ? (int) $specification->feature_value_id : null,
                featureValueName: isset($specification->feature_value_name) ? (string) $specification->feature_value_name : null,
                featureValueShortCode: isset($specification->feature_value_short_code) ? (string) $specification->feature_value_short_code : null,
                template: (string) $specification->template,
                name: isset($specification->name) ? (string) $specification->name : null,
                text: isset($specification->text) ? (string) $specification->text : null,
                details: $this->jsonArray($specification->details),
                createdAt: isset($specification->created_at) ? (string) $specification->created_at : null,
                updatedAt: isset($specification->updated_at) ? (string) $specification->updated_at : null,
            ))
            ->values();
    }

    /**
     * Converts a raw modification SQL row to a normalized intermediate row.
     *
     * @return array{
     *     id: int,
     *     vehicle_id: int,
     *     ms_id: int,
     *     mod_id: int,
     *     year_from: ?int,
     *     year_to: ?int,
     *     description: ?string,
     *     type: string,
     *     brake_system_type: ?string,
     *     power_ps: ?int,
     *     power_kw: ?int,
     *     engine_type: ?string,
     *     gear_type: ?string,
     *     drive_type: ?string,
     *     localized_name: ?string,
     *     number_of_cylinders: ?int,
     *     capacity_lt: ?float
     * }
     */
    private function modificationRow(object $modification): array
    {
        return [
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
        ];
    }

    /**
     * Converts a raw engine SQL row to a local CRM engine projection.
     */
    private function engine(object $engine): VehicleCrmEngineDTO
    {
        return new VehicleCrmEngineDTO(
            id: (int) $engine->id,
            engId: (int) $engine->eng_id,
            codeEngine: isset($engine->code_engine) ? (string) $engine->code_engine : null,
            engineCapacity: isset($engine->engine_capacity) ? (string) $engine->engine_capacity : null,
            cylinderCount: isset($engine->cylinder_count) ? (int) $engine->cylinder_count : null,
            cylinderDiameter: isset($engine->cylinder_diameter) ? (float) $engine->cylinder_diameter : null,
            engPowerKwStart: isset($engine->eng_power_kw_start) ? (int) $engine->eng_power_kw_start : null,
            engPowerKwUpto: isset($engine->eng_power_kw_upto) ? (int) $engine->eng_power_kw_upto : null,
            engPowerPsStart: isset($engine->eng_power_ps_start) ? (int) $engine->eng_power_ps_start : null,
            engPowerPsUpto: isset($engine->eng_power_ps_upto) ? (int) $engine->eng_power_ps_upto : null,
            engNumberOfValves: isset($engine->eng_number_of_valves) ? (int) $engine->eng_number_of_valves : null,
            engFuelType: isset($engine->eng_fuel_type) ? (string) $engine->eng_fuel_type : null,
            groupId: isset($engine->group_id) ? (int) $engine->group_id : null,
        );
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
}
