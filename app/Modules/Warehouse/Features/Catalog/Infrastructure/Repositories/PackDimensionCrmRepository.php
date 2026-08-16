<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\PackDimensionCrmRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm\NomenclatureCrmOptionDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmListItemDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmPageDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\Crm\PackDimensionCrmPaginationMetaDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionCrmReadQueryDTO;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\PackDimension;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Eloquent adapter CRM read API упаковочных размеров Warehouse.
 */
final readonly class PackDimensionCrmRepository implements PackDimensionCrmRepositoryInterface
{
    private const int OPTION_LIMIT = 1000;

    /**
     * Читает постраничный список упаковочных размеров для CRM.
     *
     * Шаги:
     * 1. Собирает базовый query builder упаковочных размеров.
     * 2. Применяет фильтры, search и сортировку из read-query DTO.
     * 3. Выполняет пагинацию.
     * 4. Маппит строки БД в DTO страницы.
     */
    public function paginate(PackDimensionCrmReadQueryDTO $query): PackDimensionCrmPageDTO
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
            ->map(fn (PackDimension $packDimension): PackDimensionCrmListItemDTO => $this->item($packDimension))
            ->values();

        return new PackDimensionCrmPageDTO(
            data: $items,
            meta: $this->meta($paginator),
        );
    }

    /**
     * Читает detail-снимок упаковочного размера по id.
     *
     * Шаги:
     * 1. Добавляет фильтр по внутреннему id к базовому query builder.
     * 2. Возвращает `null`, если упаковочный размер не найден.
     * 3. Маппит найденную строку БД в DTO.
     */
    public function findById(int $id): ?PackDimensionCrmListItemDTO
    {
        $packDimension = $this->baseQuery()
            ->whereKey($id)
            ->first();

        return $packDimension === null ? null : $this->item($packDimension);
    }

    /**
     * Читает type options для CRM-формы упаковочного размера.
     *
     * Шаги:
     * 1. Собирает query builder типов.
     * 2. Применяет выбранный id или search-фильтр.
     * 3. Ограничивает результат безопасным лимитом options endpoint-а.
     * 4. Маппит строки БД в option DTO.
     *
     * @return Collection<int, NomenclatureCrmOptionDTO>
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
            ->limit(min(max($limit, 1), self::OPTION_LIMIT))
            ->get()
            ->map(fn (Type $type): NomenclatureCrmOptionDTO => new NomenclatureCrmOptionDTO(
                id: (int) $type->id,
                label: (string) $type->name,
                meta: ['char' => (string) $type->char],
            ))
            ->values();
    }

    /**
     * Собирает базовый query builder упаковочных размеров для CRM read API.
     *
     * Шаги:
     * 1. Открывает query builder таблицы `pack_dimensions`.
     * 2. Подключает типы.
     * 3. Добавляет scalar поля и счетчик связанных комплектов.
     */
    private function baseQuery(): Builder
    {
        return PackDimension::query()
            ->select('pack_dimensions.*')
            ->with('type')
            ->withCount('kits');
    }

    /**
     * Применяет фильтры CRM списка упаковочных размеров.
     *
     * Шаги:
     * 1. Применяет multi-value фильтр `type_id`.
     * 2. Пропускает пустые значения фильтров.
     * 3. Применяет текстовый фильтр `name`.
     *
     * @param  array<string, mixed>  $фильтры
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['type_id']) && $filters['type_id'] !== '') {
            $values = is_array($filters['type_id']) ? $filters['type_id'] : [$filters['type_id']];
            $query->whereIn('pack_dimensions.type_id', $values);
        }

        if (isset($filters['name']) && trim((string) $filters['name']) !== '') {
            $query->where('pack_dimensions.name', 'ilike', '%'.trim((string) $filters['name']).'%');
        }
    }

    /**
     * Применяет search к CRM списку упаковочных размеров.
     *
     * Шаги:
     * 1. Нормализует поисковую строку.
     * 2. Пропускает пустой search.
     * 3. Ищет по названию упаковочного размера и типу.
     * 4. Для числового search дополнительно проверяет внутренний id.
     */
    private function applySearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('pack_dimensions.name', 'ilike', "%{$search}%")
                ->orWhereHas('type', function (Builder $type) use ($search): void {
                    $type->where('types.name', 'ilike', "%{$search}%");
                });

            if (is_numeric($search)) {
                $query->orWhere('pack_dimensions.id', (int) $search);
            }
        });
    }

    /**
     * Применяет сортировку CRM списка упаковочных размеров.
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

        if ($field === 'type_name') {
            $query
                ->leftJoin('types', 'types.id', '=', 'pack_dimensions.type_id')
                ->select('pack_dimensions.*')
                ->orderBy('types.name', $direction);

            return;
        }

        $column = match ($field) {
            'id' => 'pack_dimensions.id',
            'name' => 'pack_dimensions.name',
            'weight' => 'pack_dimensions.weight',
            'width' => 'pack_dimensions.width',
            'height' => 'pack_dimensions.height',
            'length' => 'pack_dimensions.length',
            'price' => 'pack_dimensions.price',
            default => 'pack_dimensions.id',
        };

        $query->orderBy($column, $direction);
    }

    /**
     * Маппит SQL projection упаковочного размера в CRM DTO.
     *
     * Шаги:
     * 1. Приводит scalar поля projection к типам DTO.
     * 2. Добавляет данные типа и счетчик связанных комплектов.
     * 3. Возвращает `PackDimensionCrmListItemDTO`.
     */
    private function item(PackDimension $packDimension): PackDimensionCrmListItemDTO
    {
        return new PackDimensionCrmListItemDTO(
            id: (int) $packDimension->id,
            name: (string) $packDimension->name,
            weight: (int) $packDimension->weight,
            width: (int) $packDimension->width,
            height: (int) $packDimension->height,
            length: (int) $packDimension->length,
            price: (int) $packDimension->price,
            typeId: (int) $packDimension->type_id,
            typeName: (string) $packDimension->type->name,
            typeChar: (string) $packDimension->type->char,
            generated: (bool) $packDimension->generated,
            kitsCount: (int) $packDimension->kits_count,
            createdAt: $packDimension->created_at === null ? null : (string) $packDimension->created_at,
            updatedAt: $packDimension->updated_at === null ? null : (string) $packDimension->updated_at,
        );
    }

    /**
     * Маппит Laravel paginator в CRM DTO метаданных пагинации.
     *
     * Шаги:
     * 1. Читает текущую страницу, per-page, total и last page.
     * 2. Возвращает типизированный DTO метаданных для презентера ответа.
     */
    private function meta(LengthAwarePaginator $paginator): PackDimensionCrmPaginationMetaDTO
    {
        return new PackDimensionCrmPaginationMetaDTO(
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
        );
    }
}
