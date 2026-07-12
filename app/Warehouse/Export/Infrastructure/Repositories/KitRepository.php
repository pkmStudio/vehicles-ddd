<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Infrastructure\Repositories;

use App\Warehouse\Export\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Warehouse\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Warehouse\Export\Domain\DTOs\KitExportSortDTO;
use App\Warehouse\Export\Domain\ModelData\KitData;
use App\Warehouse\Export\Infrastructure\Models\Kit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Читает Warehouse-наборы для экспорта через Eloquent-копию модели фичи.
 */
final readonly class KitRepository implements KitRepositoryInterface
{
    private const array SORT_COLUMNS = [
        'id' => 'id',
        'type_id' => 'type_id',
        'complectation' => 'complectation',
        'is_active' => 'is_active',
        'is_sale_separately' => 'is_sale_separately',
    ];

    /**
     * Возвращает наборы с данными, нужными для листа экспорта, с учётом внешних фильтров.
     *
     * Шаги:
     * 1) Собрать базовый запрос с нужными связями.
     * 2) Применить явные фильтры payload.
     * 3) Применить сортировку и вернуть коллекцию KitData.
     *
     * @return Collection<int, KitData>
     */
    public function all(KitExportFiltersDTO $filters, KitExportSortDTO $sort): Collection
    {
        $query = Kit::query()
            ->with(['type', 'packDimension', 'nomenclatures']);

        $this->applyFilters(
            query: $query,
            filters: $filters,
        );
        $this->applySort(
            query: $query,
            sort: $sort,
        );

        $kits = $query->get();

        return KitData::collect($kits, Collection::class);
    }

    /**
     * Добавляет в запрос все непустые фильтры Kit Export.
     */
    private function applyFilters(Builder $query, KitExportFiltersDTO $filters): void
    {
        if ($filters->ids !== []) {
            $query->whereIn('id', $filters->ids);
        }

        if ($filters->typeIds !== []) {
            $query->whereIn('type_id', $filters->typeIds);
        }

        if ($filters->isActive !== null) {
            $query->where('is_active', $filters->isActive);
        }

        if ($filters->isSaleSeparately !== null) {
            $query->where('is_sale_separately', $filters->isSaleSeparately);
        }

        if ($filters->nomenclaturePartNumbers !== []) {
            $query->whereHas(
                relation: 'nomenclatures',
                callback: function (Builder $query) use ($filters): void {
                    $query->whereIn('part_number', $filters->nomenclaturePartNumbers);
                },
            );
        }

        if ($filters->search !== null && $filters->search !== '') {
            $this->applySearch(
                query: $query,
                search: $filters->search,
            );
        }
    }

    /**
     * Добавляет совместимый со старой таблицей поиск по комплекту и составу.
     */
    private function applySearch(Builder $query, string $search): void
    {
        $searchPattern = sprintf('%%%s%%', $search);

        $query->where(function (Builder $query) use ($searchPattern): void {
            $query
                ->where('complectation', 'like', $searchPattern)
                ->orWhereHas(
                    relation: 'nomenclatures',
                    callback: function (Builder $query) use ($searchPattern): void {
                        $query
                            ->where('part_number', 'like', $searchPattern)
                            ->orWhere('name', 'like', $searchPattern);
                    },
                );
        });
    }

    /**
     * Добавляет сортировку Kit Export и стабильный tie-breaker по id.
     */
    private function applySort(Builder $query, KitExportSortDTO $sort): void
    {
        $column = self::SORT_COLUMNS[$sort->field] ?? self::SORT_COLUMNS['id'];

        $query->orderBy($column, $sort->direction);

        if ($column !== self::SORT_COLUMNS['id']) {
            $query->orderBy('id');
        }
    }
}
