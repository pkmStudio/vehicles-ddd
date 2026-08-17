<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmEngineDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmModificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmModificationEngineDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPartSpecificationDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Feature;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\FeatureValue;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories\VehicleCrmPageDTOFactory;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories\VehicleCrmRelationPageDTOFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Eloquent read adapter CRM API каталога Vehicles.
 */
final readonly class VehicleCrmRepository implements VehicleCrmRepositoryInterface
{
    /**
     * Инициализирует factory page DTO.
     */
    public function __construct(
        private VehicleCrmPageDTOFactory $pageFactory,
        private VehicleCrmRelationPageDTOFactory $relationPageFactory,
    ) {}

    /**
     * Читает постраничный список автомобилей для CRM.
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
            ->map(fn (Vehicle $vehicle): VehicleCrmListItemDTO => $this->item($vehicle))
            ->values();

        return $this->pageFactory->make($items, $paginator);
    }

    /**
     * Читает detail-снимок автомобиля по id.
     */
    public function findById(int $id): ?VehicleCrmListItemDTO
    {
        $vehicle = $this->baseQuery()
            ->whereKey($id)
            ->first();

        return $vehicle === null ? null : $this->item($vehicle);
    }

    /**
     * Читает постраничные модификации автомобиля для CRM формы.
     *
     * Шаги:
     * 1. Строит query модификаций по id автомобиля.
     * 2. Применяет фильтры, поиск и сортировку relation endpoint.
     * 3. Пагинирует результат и мапит модели в CRM DTO.
     */
    public function modifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO
    {
        $builder = Modification::query()
            ->where('vehicle_id', $vehicleId);

        $this->applyModificationFilters($builder, $query->filters);
        $this->applyModificationSearch($builder, $query->search);
        $this->applyModificationSort($builder, $query->sort);

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page,
        );

        $items = collect($paginator->items())
            ->map(fn (Modification $modification): VehicleCrmModificationDTO => $this->modification($modification, collect()))
            ->values();

        return $this->relationPageFactory->make($items, $paginator);
    }

    /**
     * Читает постраничные двигатели, связанные с модификациями автомобиля.
     *
     * Шаги:
     * 1. Строит join query двигателей через `engine_modification` и `modifications`.
     * 2. Применяет фильтры, поиск и сортировку relation endpoint.
     * 3. Пагинирует результат и мапит модели в CRM DTO с `modification_id`.
     */
    public function engines(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO
    {
        $builder = Engine::query()
            ->join('engine_modification', 'engine_modification.engine_id', '=', 'engines.id')
            ->join('modifications', 'modifications.id', '=', 'engine_modification.modification_id')
            ->where('modifications.vehicle_id', $vehicleId)
            ->select('engines.*', 'engine_modification.modification_id', 'engine_modification.provider as relation_provider');

        $this->applyEngineFilters($builder, $query->filters);
        $this->applyEngineSearch($builder, $query->search);
        $this->applyEngineSort($builder, $query->sort);

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page,
        );

        $items = collect($paginator->items())
            ->map(fn (Engine $engine): VehicleCrmModificationEngineDTO => $this->modificationEngine($engine))
            ->values();

        return $this->relationPageFactory->make($items, $paginator);
    }

    /**
     * Читает постраничные спецификации деталей автомобиля.
     *
     * Шаги:
     * 1. Строит query part specifications по owner автомобиля.
     * 2. Применяет фильтры, поиск и сортировку relation endpoint.
     * 3. Пагинирует результат и мапит модели в CRM DTO.
     */
    public function partSpecifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO
    {
        $builder = PartSpecification::query()
            ->with(['featureValue.feature'])
            ->where('partable_type', 'vehicle')
            ->where('partable_id', $vehicleId);

        $this->applyPartSpecificationFilters($builder, $query->filters);
        $this->applyPartSpecificationSearch($builder, $query->search);
        $this->applyPartSpecificationSort($builder, $query->sort);

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page,
        );

        $items = collect($paginator->items())
            ->map(fn (PartSpecification $specification): VehicleCrmPartSpecificationDTO => $this->partSpecification($specification))
            ->values();

        return $this->relationPageFactory->make($items, $paginator);
    }

    /**
     * Читает compact search options автомобилей для CRM autocomplete.
     *
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        $builder = $this->baseQuery();
        $this->applySearch($builder, $query);

        return $builder
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'vehicles.manufacturer_id')
            ->select('vehicles.*')
            ->orderBy('manufacturers.name')
            ->orderBy('vehicles.name')
            ->limit(min(max($limit, 1), 50))
            ->get()
            ->map(fn (Vehicle $vehicle): VehicleCrmSearchItemDTO => $this->searchItem($vehicle))
            ->values();
    }

    /**
     * Читает feature options автомобилей для CRM filters.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function featureOptions(): Collection
    {
        return Feature::query()
            ->where('entity_type', 'vehicle')
            ->orderBy('name')
            ->get()
            ->map(fn (Feature $feature): VehicleCrmFeatureOptionDTO => new VehicleCrmFeatureOptionDTO(
                id: (int) $feature->id,
                label: (string) $feature->name,
            ))
            ->values();
    }

    /**
     * Читает feature value options одной характеристики.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValueOptions(int $featureId): Collection
    {
        if ($featureId <= 0) {
            return collect();
        }

        return FeatureValue::query()
            ->where('feature_id', $featureId)
            ->orderBy('name')
            ->get()
            ->map(fn (FeatureValue $value): VehicleCrmFeatureValueOptionDTO => new VehicleCrmFeatureValueOptionDTO(
                id: (int) $value->id,
                featureId: (int) $value->feature_id,
                label: (string) $value->name,
                shortCode: (string) $value->short_code,
            ))
            ->values();
    }

    /**
     * Собирает общий vehicle query для CRM read API.
     */
    private function baseQuery(): Builder
    {
        return Vehicle::query()
            ->select('vehicles.*')
            ->with(['manufacturer', 'parent']);
    }

    /**
     * Применяет CRM filters к vehicle query.
     *
     * @param  array<string, string|int|float|bool|array<int, string|int|float|bool>|null>  $filters
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

        if (isset($filters['name']) && trim((string) $filters['name']) !== '') {
            $query->where('vehicles.name', 'ilike', '%'.trim((string) $filters['name']).'%');
        }

        if (isset($filters['manufacturer_name']) && trim((string) $filters['manufacturer_name']) !== '') {
            $manufacturerName = trim((string) $filters['manufacturer_name']);
            $query->whereHas('manufacturer', function (Builder $manufacturer) use ($manufacturerName): void {
                $manufacturer->where('manufacturers.name', 'ilike', "%{$manufacturerName}%");
            });
        }

        if (array_key_exists('is_allow', $filters) && $filters['is_allow'] !== '') {
            $query->where('vehicles.is_allow', filter_var($filters['is_allow'], FILTER_VALIDATE_BOOL));
        }
    }

    /**
     * Применяет search по автомобилям и производителям.
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
                ->orWhere('vehicles.generation', 'ilike', "%{$search}%")
                ->orWhere('vehicles.localized_name', 'ilike', "%{$search}%")
                ->orWhereHas('manufacturer', function (Builder $manufacturer) use ($search): void {
                    $manufacturer->where('manufacturers.name', 'ilike', "%{$search}%");
                });

            if (is_numeric($search)) {
                $query->orWhere('vehicles.ms_id', (int) $search);
            }
        });
    }

    /**
     * Применяет whitelisted CRM sort expression.
     */
    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if ($field === 'manufacturer_name') {
            $query
                ->leftJoin('manufacturers', 'manufacturers.id', '=', 'vehicles.manufacturer_id')
                ->select('vehicles.*')
                ->orderBy('manufacturers.name', $direction);

            return;
        }

        $column = match ($field) {
            'id' => 'vehicles.id',
            'ms_id' => 'vehicles.ms_id',
            'mfa_id' => 'vehicles.mfa_id',
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
     * @param  array<string, string|int|float|bool|array<int, string|int|float|bool>|null>  $filters
     */
    private function applyModificationFilters(Builder $query, array $filters): void
    {
        foreach (['type', 'provider'] as $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn($field, $values);
        }

        foreach (['description', 'localized_name'] as $field) {
            if (isset($filters[$field]) && trim((string) $filters[$field]) !== '') {
                $query->where($field, 'ilike', '%'.trim((string) $filters[$field]).'%');
            }
        }
    }

    private function applyModificationSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('description', 'ilike', "%{$search}%")
                ->orWhere('localized_name', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query
                    ->orWhere('id', (int) $search)
                    ->orWhere('mod_id', (int) $search)
                    ->orWhere('ms_id', (int) $search);
            }
        });
    }

    private function applyModificationSort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'id',
            'mod_id' => 'mod_id',
            'ms_id' => 'ms_id',
            'year_from' => 'year_from',
            'year_to' => 'year_to',
            'description' => 'description',
            'provider' => 'provider',
            default => 'year_from',
        };

        $query->orderBy($column, $direction)->orderBy('id');
    }

    /**
     * @param  array<string, string|int|float|bool|array<int, string|int|float|bool>|null>  $filters
     */
    private function applyEngineFilters(Builder $query, array $filters): void
    {
        foreach (['engine_modification.modification_id' => 'modification_id', 'engines.provider' => 'provider', 'engines.group_id' => 'group_id'] as $column => $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn($column, $values);
        }

        if (isset($filters['code_engine']) && trim((string) $filters['code_engine']) !== '') {
            $query->where('engines.code_engine', 'ilike', '%'.trim((string) $filters['code_engine']).'%');
        }
    }

    private function applyEngineSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('engines.code_engine', 'ilike', "%{$search}%")
                ->orWhere('engines.fuel_type', 'ilike', "%{$search}%");

            if (is_numeric($search)) {
                $query
                    ->orWhere('engines.id', (int) $search)
                    ->orWhere('engines.eng_id', (int) $search)
                    ->orWhere('engines.group_id', (int) $search);
            }
        });
    }

    private function applyEngineSort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'engines.id',
            'eng_id' => 'engines.eng_id',
            'modification_id' => 'engine_modification.modification_id',
            'code_engine' => 'engines.code_engine',
            'engine_capacity' => 'engines.engine_capacity',
            'provider' => 'engines.provider',
            'group_id' => 'engines.group_id',
            default => 'engine_modification.modification_id',
        };

        $query->orderBy($column, $direction)->orderBy('engines.id');
    }

    /**
     * @param  array<string, string|int|float|bool|array<int, string|int|float|bool>|null>  $filters
     */
    private function applyPartSpecificationFilters(Builder $query, array $filters): void
    {
        foreach (['template', 'feature_value_id'] as $field) {
            if (! isset($filters[$field]) || $filters[$field] === '') {
                continue;
            }

            $values = is_array($filters[$field]) ? $filters[$field] : [$filters[$field]];
            $query->whereIn($field, $values);
        }

        if (isset($filters['feature_id']) && $filters['feature_id'] !== '') {
            $values = is_array($filters['feature_id']) ? $filters['feature_id'] : [$filters['feature_id']];
            $query->whereHas('featureValue', function (Builder $featureValue) use ($values): void {
                $featureValue->whereIn('feature_id', $values);
            });
        }

        if (isset($filters['name']) && trim((string) $filters['name']) !== '') {
            $query->where('name', 'ilike', '%'.trim((string) $filters['name']).'%');
        }
    }

    private function applyPartSpecificationSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('text', 'ilike', "%{$search}%")
                ->orWhere('template', 'ilike', "%{$search}%")
                ->orWhereHas('featureValue', function (Builder $featureValue) use ($search): void {
                    $featureValue->where('name', 'ilike', "%{$search}%");
                });

            if (is_numeric($search)) {
                $query->orWhere('id', (int) $search);
            }
        });
    }

    private function applyPartSpecificationSort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        $column = match ($field) {
            'id' => 'id',
            'template' => 'template',
            'name' => 'name',
            'feature_value_id' => 'feature_value_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            default => 'template',
        };

        $query->orderBy($column, $direction)->orderBy('id');
    }

    private function item(Vehicle $vehicle): VehicleCrmListItemDTO
    {
        return new VehicleCrmListItemDTO(
            id: (int) $vehicle->id,
            parentId: $vehicle->parent_id === null ? null : (int) $vehicle->parent_id,
            parentMsId: $vehicle->parent?->ms_id === null ? null : (int) $vehicle->parent->ms_id,
            manufacturerId: (int) $vehicle->manufacturer_id,
            manufacturerName: $vehicle->manufacturer?->name === null ? null : (string) $vehicle->manufacturer->name,
            mfaId: (int) $vehicle->mfa_id,
            msId: (int) $vehicle->ms_id,
            name: (string) $vehicle->name,
            localizedName: $vehicle->localized_name === null ? null : (string) $vehicle->localized_name,
            excelTableId: $vehicle->excel_table_id === null ? null : (string) $vehicle->excel_table_id,
            generation: (string) $vehicle->generation,
            generationShort: $vehicle->generation_short === null ? null : (string) $vehicle->generation_short,
            generationYearFrom: (int) $vehicle->generation_year_from,
            generationYearTo: $vehicle->generation_year_to === null ? null : (int) $vehicle->generation_year_to,
            type: $vehicle->type->value,
            typeCarcase: $vehicle->type_carcase->value,
            provider: $vehicle->provider->value,
            steeringType: $vehicle->steering_type->value,
            isAllow: (bool) $vehicle->is_allow,
        );
    }

    private function searchItem(Vehicle $vehicle): VehicleCrmSearchItemDTO
    {
        return new VehicleCrmSearchItemDTO(
            id: (int) $vehicle->id,
            label: sprintf(
                '%s | %s %s %s | %s (%s-%s)',
                $vehicle->ms_id,
                $vehicle->manufacturer?->name,
                $vehicle->name,
                $vehicle->generation,
                $vehicle->localized_name ?: '',
                $vehicle->generation_year_from,
                $vehicle->generation_year_to ?: 'н.в.',
            ),
            msId: (int) $vehicle->ms_id,
            manufacturer: $vehicle->manufacturer?->name === null ? null : (string) $vehicle->manufacturer->name,
        );
    }

    private function modification(Modification $modification, ?Collection $engines = null): VehicleCrmModificationDTO
    {
        return new VehicleCrmModificationDTO(
            id: (int) $modification->id,
            vehicleId: (int) $modification->vehicle_id,
            msId: (int) $modification->ms_id,
            modId: (int) $modification->mod_id,
            yearFrom: (int) $modification->year_from,
            yearTo: $modification->year_to === null ? null : (int) $modification->year_to,
            description: (string) $modification->description,
            descriptionShort: $modification->description_short === null ? null : (string) $modification->description_short,
            type: $modification->type->value,
            brakeSystemType: $modification->brake_system_type?->value,
            powerPs: (int) $modification->power_ps,
            powerKw: (int) $modification->power_kw,
            engineType: $modification->engine_type->value,
            gearType: $modification->gear_type?->value,
            driveType: $modification->drive_type?->value,
            localizedName: $modification->localized_name === null ? null : (string) $modification->localized_name,
            numberOfCylinders: $modification->number_of_cylinders === null ? null : (int) $modification->number_of_cylinders,
            capacityLt: $modification->capacity_lt === null ? null : (float) $modification->capacity_lt,
            provider: $modification->provider->value,
            allowChangeFields: $modification->allow_change_fields,
            engines: ($engines ?? $modification->engines
                ->map(fn (Engine $engine): VehicleCrmEngineDTO => $this->engine($engine))
                ->values()),
        );
    }

    private function modificationEngine(Engine $engine): VehicleCrmModificationEngineDTO
    {
        return new VehicleCrmModificationEngineDTO(
            modificationId: (int) $engine->getAttribute('modification_id'),
            id: (int) $engine->id,
            engId: (int) $engine->eng_id,
            codeEngine: (string) $engine->code_engine,
            powerKwStart: (int) $engine->power_kw_start,
            powerPsStart: (int) $engine->power_ps_start,
            fuelType: $engine->fuel_type->value,
            provider: $engine->provider->value,
            relationProvider: (string) $engine->getAttribute('relation_provider'),
            engineCapacity: $engine->engine_capacity === null ? null : (float) $engine->engine_capacity,
            cylinderCount: $engine->cylinder_count === null ? null : (int) $engine->cylinder_count,
            cylinderDiameter: $engine->cylinder_diameter === null ? null : (float) $engine->cylinder_diameter,
            powerKwUpto: $engine->power_kw_upto === null ? null : (int) $engine->power_kw_upto,
            powerPsUpto: $engine->power_ps_upto === null ? null : (int) $engine->power_ps_upto,
            numberOfValves: $engine->number_of_valves === null ? null : (int) $engine->number_of_valves,
            groupId: $engine->group_id === null ? null : (int) $engine->group_id,
            allowChangeFields: $engine->allow_change_fields,
        );
    }

    private function engine(Engine $engine): VehicleCrmEngineDTO
    {
        return new VehicleCrmEngineDTO(
            id: (int) $engine->id,
            engId: (int) $engine->eng_id,
            codeEngine: (string) $engine->code_engine,
            powerKwStart: (int) $engine->power_kw_start,
            powerPsStart: (int) $engine->power_ps_start,
            fuelType: $engine->fuel_type->value,
            provider: $engine->provider->value,
            relationProvider: $this->engineRelationProvider($engine),
            engineCapacity: $engine->engine_capacity === null ? null : (float) $engine->engine_capacity,
            cylinderCount: $engine->cylinder_count === null ? null : (int) $engine->cylinder_count,
            cylinderDiameter: $engine->cylinder_diameter === null ? null : (float) $engine->cylinder_diameter,
            powerKwUpto: $engine->power_kw_upto === null ? null : (int) $engine->power_kw_upto,
            powerPsUpto: $engine->power_ps_upto === null ? null : (int) $engine->power_ps_upto,
            numberOfValves: $engine->number_of_valves === null ? null : (int) $engine->number_of_valves,
            groupId: $engine->group_id === null ? null : (int) $engine->group_id,
            allowChangeFields: $engine->allow_change_fields,
        );
    }

    private function engineRelationProvider(Engine $engine): ?string
    {
        $relationProvider = $engine->getAttribute('relation_provider') ?? $engine->pivot?->provider;

        if ($relationProvider === null) {
            return null;
        }

        return is_string($relationProvider) ? $relationProvider : $relationProvider->value;
    }

    private function partSpecification(PartSpecification $specification): VehicleCrmPartSpecificationDTO
    {
        $featureValue = $specification->featureValue;
        $feature = $featureValue?->feature;

        return new VehicleCrmPartSpecificationDTO(
            id: (int) $specification->id,
            partableType: $specification->partable_type->value,
            partableId: (int) $specification->partable_id,
            featureId: $feature?->id === null ? null : (int) $feature->id,
            featureName: $feature?->name === null ? null : (string) $feature->name,
            featureValueId: $featureValue?->id === null ? null : (int) $featureValue->id,
            featureValueName: $featureValue?->name === null ? null : (string) $featureValue->name,
            featureValueShortCode: $featureValue?->short_code === null ? null : (string) $featureValue->short_code,
            template: $specification->template->value,
            name: $specification->name === null ? null : (string) $specification->name,
            text: $specification->text === null ? null : (string) $specification->text,
            details: $specification->details,
            createdAt: $specification->created_at === null ? null : (string) $specification->created_at,
            updatedAt: $specification->updated_at === null ? null : (string) $specification->updated_at,
        );
    }
}
