<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use Illuminate\Support\Collection;

/**
 * Читает Warehouse-наборы для Catalog-мутаций.
 */
final readonly class KitRepository implements KitRepositoryInterface
{
    /**
     * Возвращает набор по внутреннему идентификатору или null.
     *
     * Шаги:
     * 1) Собрать Eloquent query по входному признаку.
     * 2) Получить первую подходящую запись каталога.
     * 3) Преобразовать найденную модель в Data или вернуть null.
     */
    public function findById(int $id): ?KitData
    {
        return $this->findByColumn('id', $id);
    }

    /**
     * Возвращает набор по import_hash или null.
     *
     * Шаги:
     * 1) Собрать Eloquent query по входному признаку.
     * 2) Получить первую подходящую запись каталога.
     * 3) Преобразовать найденную модель в Data или вернуть null.
     */
    public function findByImportHash(string $importHash): ?KitData
    {
        return $this->findByColumn('import_hash', $importHash);
    }

    /**
     * Возвращает ids наборов упаковочного размера.
     *
     * @return Collection<int, int>
     *
     * Шаги:
     * 1) Собрать Eloquent query по внешнему ключу связи.
     * 2) Выбрать только идентификаторы подходящих записей.
     * 3) Вернуть Support Collection с id для каскадной операции.
     */
    public function findIdsByPackDimensionId(int $packDimensionId): Collection
    {
        return Kit::query()
            ->where('pack_dimension_id', $packDimensionId)
            ->pluck('id')
            ->values();
    }

    private function findByColumn(string $column, int|string $value): ?KitData
    {
        return KitData::optional(
            Kit::query()
                ->where($column, $value)
                ->first(),
        );
    }
}
