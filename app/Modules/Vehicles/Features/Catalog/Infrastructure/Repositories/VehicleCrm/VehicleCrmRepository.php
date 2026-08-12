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
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Feature;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\FeatureValue;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories\VehicleCrmPageDTOFactory;
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
    public function findById(int $id): ?VehicleCrmDetailDTO
    {
        $vehicle = $this->baseQuery()
            ->with([
                'modifications' => fn (Builder $query): Builder => $query
                    ->orderBy('year_from')
                    ->orderBy('description'),
                'modifications.engines' => fn (Builder $query): Builder => $query->orderBy('code_engine'),
                'partSpecifications' => fn (Builder $query): Builder => $query
                    ->orderBy('template')
                    ->orderBy('id'),
                'partSpecifications.featureValue.feature',
            ])
            ->whereKey($id)
            ->first();

        if ($vehicle === null) {
            return null;
        }

        return new VehicleCrmDetailDTO(
            vehicle: $this->item($vehicle),
            modifications: $this->modifications($vehicle),
            partSpecifications: $this->partSpecifications($vehicle),
        );
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
     * Читает manufacturer options для CRM forms.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturerOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = Manufacturer::query()->orderBy('name');

        if ($id !== null) {
            $builder->whereKey($id);
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
            ->map(fn (Manufacturer $manufacturer): VehicleCrmManufacturerOptionDTO => new VehicleCrmManufacturerOptionDTO(
                id: (int) $manufacturer->id,
                mfaId: (int) $manufacturer->mfa_id,
                label: (string) $manufacturer->name,
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

    /**
     * @return Collection<int, VehicleCrmModificationDTO>
     */
    private function modifications(Vehicle $vehicle): Collection
    {
        return $vehicle->modifications
            ->map(fn (Modification $modification): VehicleCrmModificationDTO => $this->modification($modification))
            ->values();
    }

    private function modification(Modification $modification): VehicleCrmModificationDTO
    {
        return new VehicleCrmModificationDTO(
            id: (int) $modification->id,
            vehicleId: (int) $modification->vehicle_id,
            msId: (int) $modification->ms_id,
            modId: (int) $modification->mod_id,
            yearFrom: $modification->year_from === null ? null : (int) $modification->year_from,
            yearTo: $modification->year_to === null ? null : (int) $modification->year_to,
            description: $modification->description === null ? null : (string) $modification->description,
            type: $modification->type->value,
            brakeSystemType: $modification->brake_system_type?->value,
            powerPs: $modification->power_ps === null ? null : (int) $modification->power_ps,
            powerKw: $modification->power_kw === null ? null : (int) $modification->power_kw,
            engineType: $modification->engine_type?->value,
            gearType: $modification->gear_type?->value,
            driveType: $modification->drive_type?->value,
            localizedName: $modification->localized_name === null ? null : (string) $modification->localized_name,
            numberOfCylinders: $modification->number_of_cylinders === null ? null : (int) $modification->number_of_cylinders,
            capacityLt: $modification->capacity_lt === null ? null : (float) $modification->capacity_lt,
            provider: $modification->provider->value,
            allowChangeFields: $modification->allow_change_fields,
            engines: $modification->engines
                ->map(fn (Engine $engine): VehicleCrmEngineDTO => $this->engine($engine))
                ->values(),
        );
    }

    private function engine(Engine $engine): VehicleCrmEngineDTO
    {
        return new VehicleCrmEngineDTO(
            id: (int) $engine->id,
            engId: (int) $engine->eng_id,
            codeEngine: $engine->code_engine === null ? null : (string) $engine->code_engine,
            engineCapacity: $engine->engine_capacity === null ? null : (string) $engine->engine_capacity,
            cylinderCount: $engine->cylinder_count === null ? null : (int) $engine->cylinder_count,
            cylinderDiameter: $engine->cylinder_diameter === null ? null : (float) $engine->cylinder_diameter,
            powerKwStart: $engine->power_kw_start === null ? null : (int) $engine->power_kw_start,
            powerKwUpto: $engine->power_kw_upto === null ? null : (int) $engine->power_kw_upto,
            powerPsStart: $engine->power_ps_start === null ? null : (int) $engine->power_ps_start,
            powerPsUpto: $engine->power_ps_upto === null ? null : (int) $engine->power_ps_upto,
            numberOfValves: $engine->number_of_valves === null ? null : (int) $engine->number_of_valves,
            fuelType: $engine->fuel_type?->value,
            groupId: $engine->group_id === null ? null : (int) $engine->group_id,
            provider: $engine->provider->value,
            allowChangeFields: $engine->allow_change_fields,
        );
    }

    /**
     * @return Collection<int, VehicleCrmPartSpecificationDTO>
     */
    private function partSpecifications(Vehicle $vehicle): Collection
    {
        return $vehicle->partSpecifications
            ->map(fn (PartSpecification $specification): VehicleCrmPartSpecificationDTO => $this->partSpecification($specification))
            ->values();
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
