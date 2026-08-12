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
 * SQL read adapter CRM API каталога Vehicles.
 */
final readonly class VehicleCrmRepository implements VehicleCrmRepositoryInterface
{
    /**
     * Инициализирует factories для mapping CRM projections.
     *
     * Шаги:
     * 1. Получает factories search item, page, detail, modification и part specification DTO.
     * 2. Сохраняет dependencies для mapping SQL rows в локальные DTO.
     */
    public function __construct(
        private VehicleCrmSearchItemDTOFactory $searchItemFactory,
        private VehicleCrmPageDTOFactory $pageFactory,
        private VehicleCrmDetailDTOFactory $detailFactory,
        private VehicleCrmModificationDTOFactory $modificationFactory,
        private VehicleCrmPartSpecificationDTOFactory $partSpecificationFactory,
    ) {}

    /**
     * Читает постраничный список автомобилей для CRM.
     *
     * Шаги:
     * 1. Собирает базовый query builder автомобилей.
     * 2. Применяет filters, search и sort из read-query DTO.
     * 3. Выполняет pagination.
     * 4. Маппит строки БД в DTO страницы.
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
     * Читает detail-снимок автомобиля по id.
     *
     * Шаги:
     * 1. Добавляет фильтр по внутреннему id к базовому query builder.
     * 2. Возвращает `null`, если автомобиль не найден.
     * 3. Загружает вложенные модификации и спецификации деталей.
     * 4. Собирает detail DTO через factory.
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
     * Читает compact search options автомобилей для CRM autocomplete.
     *
     * Шаги:
     * 1. Собирает базовый query builder автомобилей.
     * 2. Применяет search-фильтр.
     * 3. Ограничивает результат безопасным limit.
     * 4. Маппит строки БД в search item DTO.
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
     * Читает feature options автомобилей для CRM filters.
     *
     * Шаги:
     * 1. Фильтрует features по entity type `vehicle`.
     * 2. Сортирует результат по названию.
     * 3. Маппит строки БД в option DTO.
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
     * Читает feature value options одной характеристики.
     *
     * Шаги:
     * 1. Возвращает пустую collection для невалидного feature id.
     * 2. Фильтрует feature values по feature id.
     * 3. Сортирует и маппит строки БД в option DTO.
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
     * Читает manufacturer options для CRM forms.
     *
     * Шаги:
     * 1. Собирает query builder производителей.
     * 2. Применяет selected id или search-фильтр.
     * 3. Ограничивает результат безопасным limit.
     * 4. Маппит строки БД в option DTO.
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
     * Собирает общий vehicle query для CRM read API.
     *
     * Шаги:
     * 1. Открывает query builder таблицы `vehicles`.
     * 2. Подключает parent vehicle и производителя.
     * 3. Выбирает поля projection, нужные list/detail/search сценариям.
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
     * Применяет CRM filters к vehicle query.
     *
     * Шаги:
     * 1. Применяет multi-value фильтры manufacturer/type/provider.
     * 2. Применяет текстовые фильтры name/manufacturer_name.
     * 3. Применяет boolean-фильтр `is_allow`.
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
     * Применяет search по автомобилям и производителям.
     *
     * Шаги:
     * 1. Нормализует поисковую строку.
     * 2. Пропускает пустой search.
     * 3. Ищет по названию, производителю, поколению и localized name.
     * 4. Для числового search дополнительно проверяет `ms_id`.
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
     * Применяет whitelisted CRM sort expression.
     *
     * Шаги:
     * 1. Определяет направление по префиксу `-`.
     * 2. Переводит публичное имя поля в SQL column.
     * 3. Добавляет `order by` к query builder.
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
     * Читает модификации и двигатели для CRM detail автомобиля.
     *
     * Шаги:
     * 1. Загружает модификации автомобиля в стабильном порядке.
     * 2. Возвращает пустую collection, если модификаций нет.
     * 3. Загружает двигатели, сгруппированные по id модификации.
     * 4. Маппит каждую модификацию с ее двигателями в DTO.
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
                'engines.power_kw_start',
                'engines.power_kw_upto',
                'engines.power_ps_start',
                'engines.power_ps_upto',
                'engines.number_of_valves',
                'engines.fuel_type',
                'engines.group_id',
                'engines.provider',
                'engines.allow_change_fields',
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
     * Читает спецификации деталей автомобиля для CRM detail views.
     *
     * Шаги:
     * 1. Загружает vehicle part specifications с feature/feature value metadata.
     * 2. Сортирует результат по template и id.
     * 3. Маппит строки БД в part specification DTO.
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
