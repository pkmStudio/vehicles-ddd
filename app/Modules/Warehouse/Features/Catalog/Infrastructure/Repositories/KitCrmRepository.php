<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\Crm\KitCrmPaginationMetaDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitCrmReadQueryDTO;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SQL adapter CRM read API комплектов Warehouse.
 */
final readonly class KitCrmRepository implements KitCrmRepositoryInterface
{
    private const int OPTION_LIMIT = 1000;

    /**
     * Читает постраничный список комплектов для CRM.
     *
     * Шаги:
     * 1. Собирает базовый query builder комплектов.
     * 2. Применяет фильтры, search и сортировку из read-query DTO.
     * 3. Выполняет пагинацию.
     * 4. Маппит строки БД в DTO страницы.
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
            ->map(fn (object $kit): KitCrmListItemDTO => $this->item($kit))
            ->values();

        return new KitCrmPageDTO(
            data: $items,
            meta: $this->meta($paginator),
        );
    }

    /**
     * Читает detail-снимок комплекта по id.
     *
     * Шаги:
     * 1. Добавляет фильтр по внутреннему id к базовому query builder.
     * 2. Возвращает `null`, если комплект не найден.
     * 3. Загружает номенклатуры комплекта для detail-снимка.
     * 4. Маппит строку БД в DTO.
     */
    public function findById(int $id): ?KitCrmListItemDTO
    {
        $kit = $this->baseQuery()
            ->where('kits.id', $id)
            ->first();

        if ($kit === null) {
            return null;
        }

        return $this->item($kit, $this->nomenclatures($id));
    }

    /**
     * Читает nomenclature options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Собирает query builder номенклатур с брендом.
     * 2. Применяет выбранный id или search-фильтр.
     * 3. Ограничивает результат безопасным лимитом options endpoint-а.
     * 4. Маппит строки БД в option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function nomenclatureOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = DB::table('nomenclatures')
            ->leftJoin('brands', 'brands.id', '=', 'nomenclatures.brand_id')
            ->select([
                'nomenclatures.id',
                'nomenclatures.part_number',
                'nomenclatures.name',
                'brands.name as brand_name',
            ])
            ->orderBy('brands.name')
            ->orderBy('nomenclatures.part_number');

        if ($id !== null) {
            $builder->where('nomenclatures.id', $id);
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
            ->map(fn (object $row): KitCrmOptionDTO => new KitCrmOptionDTO(
                id: (int) $row->id,
                label: trim("[{$row->part_number}] {$row->name}"),
                meta: [
                    'part_number' => (string) $row->part_number,
                    'brand_name' => $row->brand_name === null ? null : (string) $row->brand_name,
                ],
            ))
            ->values();
    }

    /**
     * Читает pack dimension options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Собирает query builder упаковочных размеров с типом.
     * 2. Применяет выбранный id или search-фильтр.
     * 3. Ограничивает результат безопасным лимитом options endpoint-а.
     * 4. Маппит строки БД в option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
     */
    public function packDimensionOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection
    {
        $builder = DB::table('pack_dimensions')
            ->leftJoin('types', 'types.id', '=', 'pack_dimensions.type_id')
            ->select([
                'pack_dimensions.id',
                'pack_dimensions.name',
                'pack_dimensions.weight',
                'pack_dimensions.width',
                'pack_dimensions.height',
                'pack_dimensions.length',
                'types.name as type_name',
            ])
            ->orderBy('pack_dimensions.name');

        if ($id !== null) {
            $builder->where('pack_dimensions.id', $id);
        } elseif ($query !== null && trim($query) !== '') {
            $search = trim($query);
            $builder->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('pack_dimensions.name', 'ilike', "%{$search}%")
                    ->orWhere('types.name', 'ilike', "%{$search}%");

                if (is_numeric($search)) {
                    $builder->orWhere('pack_dimensions.id', (int) $search);
                }
            });
        }

        return $builder
            ->limit($this->optionLimit($limit))
            ->get()
            ->map(fn (object $row): KitCrmOptionDTO => new KitCrmOptionDTO(
                id: (int) $row->id,
                label: (string) $row->name,
                meta: [
                    'type_name' => $row->type_name === null ? null : (string) $row->type_name,
                    'weight' => (int) $row->weight,
                    'dimensions' => "{$row->width} x {$row->height} x {$row->length}",
                ],
            ))
            ->values();
    }

    /**
     * Читает type options для CRM-формы комплекта.
     *
     * Шаги:
     * 1. Собирает query builder типов.
     * 2. Применяет выбранный id или search-фильтр.
     * 3. Ограничивает результат безопасным лимитом options endpoint-а.
     * 4. Маппит строки БД в option DTO.
     *
     * @return Collection<int, KitCrmOptionDTO>
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
            ->limit($this->optionLimit($limit))
            ->get()
            ->map(fn (object $row): KitCrmOptionDTO => new KitCrmOptionDTO(
                id: (int) $row->id,
                label: (string) $row->name,
                meta: ['char' => $row->char === null ? null : (string) $row->char],
            ))
            ->values();
    }

    /**
     * Собирает базовый query builder комплектов для CRM read API.
     *
     * Шаги:
     * 1. Открывает query builder таблицы `kits`.
     * 2. Подключает типы и упаковочные размеры.
     * 3. Добавляет scalar поля, счетчик и summary номенклатур.
     */
    private function baseQuery(): Builder
    {
        return DB::table('kits')
            ->leftJoin('types', 'types.id', '=', 'kits.type_id')
            ->leftJoin('pack_dimensions', 'pack_dimensions.id', '=', 'kits.pack_dimension_id')
            ->select([
                'kits.id',
                'kits.complectation',
                'kits.guarantee',
                'kits.quantity_in_package',
                'kits.quantity_package',
                'kits.complement',
                'kits.weight',
                'kits.pack_dimension_id',
                'pack_dimensions.name as pack_dimension_name',
                'kits.type_id',
                'types.name as type_name',
                'types.char as type_char',
                'kits.import_hash',
                'kits.is_sale_separately',
                'kits.is_active',
                'kits.created_at',
                'kits.updated_at',
                DB::raw('(select count(*) from kit_nomenclature where kit_nomenclature.kit_id = kits.id) as nomenclatures_count'),
                DB::raw("(select coalesce(string_agg(nomenclatures.part_number, ', ' order by kit_nomenclature.sort), '') from kit_nomenclature join nomenclatures on nomenclatures.id = kit_nomenclature.nomenclature_id where kit_nomenclature.kit_id = kits.id) as nomenclatures_list"),
            ]);
    }

    /**
     * Применяет фильтры CRM списка комплектов.
     *
     * Шаги:
     * 1. Применяет multi-value фильтры `type_id` и `pack_dimension_id`.
     * 2. Пропускает пустые значения фильтров.
     * 3. Применяет текстовый фильтр `complectation`.
     *
     * @param  array<string, mixed>  $фильтры
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

        foreach (['complectation'] as $field) {
            if (! isset($filters[$field]) || trim((string) $filters[$field]) === '') {
                continue;
            }

            $query->where("kits.{$field}", 'ilike', '%'.trim((string) $filters[$field]).'%');
        }
    }

    /**
     * Применяет полнотекстовый search к CRM списку комплектов.
     *
     * Шаги:
     * 1. Нормализует поисковую строку.
     * 2. Пропускает пустой search.
     * 3. Ищет по комплектации, типу, упаковочному размеру и номенклатурам комплекта.
     * 4. Для числового search дополнительно проверяет внутренний id комплекта.
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
                ->orWhere('types.name', 'ilike', "%{$search}%")
                ->orWhere('pack_dimensions.name', 'ilike', "%{$search}%")
                ->orWhereExists(function (Builder $exists) use ($search): void {
                    $exists
                        ->selectRaw('1')
                        ->from('kit_nomenclature')
                        ->join('nomenclatures', 'nomenclatures.id', '=', 'kit_nomenclature.nomenclature_id')
                        ->whereColumn('kit_nomenclature.kit_id', 'kits.id')
                        ->where(function (Builder $nomenclatures) use ($search): void {
                            $nomenclatures
                                ->where('nomenclatures.part_number', 'ilike', "%{$search}%")
                                ->orWhere('nomenclatures.name', 'ilike', "%{$search}%");
                        });
                });

            if (is_numeric($search)) {
                $query->orWhere('kits.id', (int) $search);
            }
        });
    }

    /**
     * Применяет сортировку CRM списка комплектов.
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
            'id' => 'kits.id',
            'complectation' => 'kits.complectation',
            'weight' => 'kits.weight',
            'guarantee' => 'kits.guarantee',
            'type_name' => 'types.name',
            'pack_dimension_name' => 'pack_dimensions.name',
            default => 'kits.id',
        };

        $query->orderBy($column, $direction);
    }

    /**
     * Читает номенклатуры комплекта для detail-снимка.
     *
     * Шаги:
     * 1. Читает связи `kit_nomenclature` для комплекта.
     * 2. Подключает номенклатуры и сохраняет порядок сортировку.
     * 3. Возвращает плоский список массивов для DTO detail.
     *
     * @return list<array{id: int, label: string, part_number: string}>
     */
    private function nomenclatures(int $kitId): array
    {
        return DB::table('kit_nomenclature')
            ->join('nomenclatures', 'nomenclatures.id', '=', 'kit_nomenclature.nomenclature_id')
            ->where('kit_nomenclature.kit_id', $kitId)
            ->orderBy('kit_nomenclature.sort')
            ->get(['nomenclatures.id', 'nomenclatures.part_number', 'nomenclatures.name'])
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'label' => trim("[{$row->part_number}] {$row->name}"),
                'part_number' => (string) $row->part_number,
            ])
            ->values()
            ->all();
    }

    /**
     * Маппит SQL projection комплекта в CRM DTO.
     *
     * Шаги:
     * 1. Приводит scalar поля projection к типам DTO.
     * 2. Добавляет summary и detail-список номенклатур.
     * 3. Возвращает `KitCrmListItemDTO`.
     *
     * @param  list<array{id: int, label: string, part_number: string}>  $nomenclatures
     */
    private function item(object $row, array $nomenclatures = []): KitCrmListItemDTO
    {
        return new KitCrmListItemDTO(
            id: (int) $row->id,
            complectation: (string) $row->complectation,
            guarantee: (int) $row->guarantee,
            quantityInPackage: (int) $row->quantity_in_package,
            quantityPackage: (int) $row->quantity_package,
            complement: (bool) $row->complement,
            weight: (int) $row->weight,
            packDimensionId: (int) $row->pack_dimension_id,
            packDimensionName: $row->pack_dimension_name === null ? null : (string) $row->pack_dimension_name,
            typeId: (int) $row->type_id,
            typeName: $row->type_name === null ? null : (string) $row->type_name,
            typeChar: $row->type_char === null ? null : (string) $row->type_char,
            importHash: $row->import_hash === null ? null : (string) $row->import_hash,
            isSaleSeparately: (bool) $row->is_sale_separately,
            isActive: (bool) $row->is_active,
            nomenclaturesCount: (int) $row->nomenclatures_count,
            nomenclaturesList: (string) $row->nomenclatures_list,
            nomenclatures: $nomenclatures,
            createdAt: $row->created_at === null ? null : (string) $row->created_at,
            updatedAt: $row->updated_at === null ? null : (string) $row->updated_at,
        );
    }

    /**
     * Маппит Laravel paginator в CRM DTO метаданных пагинации.
     *
     * Шаги:
     * 1. Читает текущую страницу, per-page, total и last page.
     * 2. Возвращает типизированный DTO метаданных для презентера ответа.
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
     *
     * Шаги:
     * 1. Принимает requested лимит.
     * 2. Ограничивает значение диапазоном `1..OPTION_LIMIT`.
     */
    private function optionLimit(int $limit): int
    {
        return min(max($limit, 1), self::OPTION_LIMIT);
    }
}
