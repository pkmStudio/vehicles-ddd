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

    /**
     * Получает фабрики DTO, которыми репозиторий переводит сырые SQL-строки в CRM read-модель.
     * Шаги:
     * 1) Сохранить фабрику для строк списка номенклатуры.
     * 2) Сохранить фабрику для paginator DTO страницы.
     * 3) Сохранить фабрику для search/type/brand option DTO.
     */
    public function __construct(
        private NomenclatureCrmListItemDTOFactory $listItemFactory,
        private NomenclatureCrmPageDTOFactory $pageFactory,
        private NomenclatureCrmSearchItemDTOFactory $searchItemFactory,
        private NomenclatureCrmTypeOptionDTOFactory $typeOptionFactory,
        private NomenclatureCrmBrandOptionDTOFactory $brandOptionFactory,
    ) {}

    /**
     * Возвращает страницу номенклатур для CRM list endpoint.
     * Шаги:
     * 1) Собрать базовый SQL-запрос с joins типов и брендов.
     * 2) Применить фильтры, полнотекстовый поиск по поддержанным колонкам и сортировку.
     * 3) Выполнить Laravel пагинацию с page/perPage из read query.
     * 4) Преобразовать строки paginator в NomenclatureCrmListItemDTO.
     * 5) Собрать NomenclatureCrmPageDTO через page фабрику.
     */
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

    /**
     * Читает одну номенклатуру для CRM detail endpoint.
     * Шаги:
     * 1) Построить тот же базовый запрос, что используется списком.
     * 2) Ограничить выборку id номенклатуры.
     * 3) Вернуть null, если строка не найдена.
     * 4) Преобразовать найденную SQL-строку в NomenclatureCrmListItemDTO.
     */
    public function findById(int $id): ?NomenclatureCrmListItemDTO
    {
        $nomenclature = $this->baseQuery()
            ->where('nomenclatures.id', $id)
            ->first();

        return $nomenclature === null ? null : $this->listItemFactory->make($nomenclature);
    }

    /**
     * Ищет номенклатуры для CRM autocomplete.
     * Шаги:
     * 1) Собрать базовый запрос со справочными joins.
     * 2) Применить общий поиск по номенклатуре, типу и бренду.
     * 3) Отсортировать результат по бренду и артикулу для стабильного списка.
     * 4) Ограничить лимит диапазоном 1..50.
     * 5) Преобразовать строки в компактные search item DTO.
     *
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
     * Возвращает варианты типов для CRM select.
     * Шаги:
     * 1) Передать общий option поиск на таблицу types.
     * 2) Сортировать варианты по id, чтобы порядок совпадал с устойчивым словарем типов.
     * 3) Преобразовать найденные строки через type option фабрику.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function typeOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->optionRows(
            table: 'types',
            orderBy: 'id',
            query: $query,
            id: $id,
            limit: $limit,
            mapper: fn (object $type): NomenclatureCrmOptionDTO => $this->typeOptionFactory->make($type),
        );
    }

    /**
     * Возвращает варианты брендов для CRM select.
     * Шаги:
     * 1) Передать общий option поиск на таблицу brands.
     * 2) Сортировать варианты по имени для удобного выбора в UI.
     * 3) Преобразовать найденные строки через brand option фабрику.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    public function brandOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        return $this->optionRows(
            table: 'brands',
            orderBy: 'name',
            query: $query,
            id: $id,
            limit: $limit,
            mapper: fn (object $brand): NomenclatureCrmOptionDTO => $this->brandOptionFactory->make($brand),
        );
    }

    /**
     * Выполняет общий SQL поиск для type/brand option endpoints.
     * Шаги:
     * 1) Построить запрос к справочной таблице с id/name/char и заданной сортировкой.
     * 2) Применить точный id поиск или текстовый поиск через applyOptionLookup().
     * 3) Ограничить лимит безопасным диапазоном 1..OPTION_LIMIT.
     * 4) Преобразовать каждую строку переданным mapper callable.
     *
     * @param  callable(object): NomenclatureCrmOptionDTO  $mapper
     * @return Collection<int, NomenclatureCrmOptionDTO>
     */
    private function optionRows(
        string $table,
        string $orderBy,
        ?string $query,
        ?int $id,
        int $limit,
        callable $mapper,
    ): Collection {
        $builder = DB::table($table)
            ->select(['id', 'name', 'char'])
            ->orderBy($orderBy);

        $this->applyOptionLookup($builder, $query, $id);

        return $builder
            ->limit(min(max($limit, 1), self::OPTION_LIMIT))
            ->get()
            ->map($mapper)
            ->values();
    }

    /**
     * Добавляет условия поиска для справочных option-запросов.
     * Шаги:
     * 1) Если передан id, выбрать только эту строку и не применять текстовый поиск.
     * 2) Если строка поиска пустая, оставить базовый запрос без дополнительных условий.
     * 3) Иначе искать по name и char через ilike.
     * 4) Если search выглядит числом, дополнительно искать по id.
     */
    private function applyOptionLookup(Builder $builder, ?string $query, ?int $id): void
    {
        if ($id !== null) {
            $builder->where('id', $id);

            return;
        }

        if ($query === null || trim($query) === '') {
            return;
        }

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

    /**
     * Собирает базовый SQL-запрос номенклатуры для CRM read endpoints.
     * Шаги:
     * 1) Читать таблицу nomenclatures как owner-таблицу складской позиции.
     * 2) Подключить type и brand левыми join, чтобы строки без справочника не выпадали.
     * 3) Выбрать только поля, нужные CRM presenter/фабрику, включая aliases справочников.
     */
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
     * Применяет фильтры CRM list query к SQL builder.
     * Шаги:
     * 1) Для type_id и brand_id поддержать одиночное значение и массив значений через whereIn.
     * 2) Пропустить отсутствующие и пустые значения фильтров.
     * 3) Для name/country/part_number применить частичный ilike поиск по колонкам nomenclatures.
     *
     * @param  array<string, mixed>  $фильтры
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

    /**
     * Применяет общий search term к CRM query.
     * Шаги:
     * 1) Нормализовать search к trimmed string.
     * 2) Для пустого search оставить запрос без изменений.
     * 3) Искать term по имени, артикулу, стране, названию типа и названию бренда.
     * 4) Для числового term дополнительно искать прямое совпадение id номенклатуры.
     */
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

    /**
     * Применяет безопасную сортировку CRM list query.
     * Шаги:
     * 1) Определить направление по префиксу '-' в сортировку.
     * 2) Снять префикс и сопоставить публичное имя поля с разрешенной SQL-колонкой.
     * 3) Для неизвестного поля fallback-нуться на nomenclatures.id.
     * 4) Добавить orderBy с выбранной колонкой и направлением.
     */
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
