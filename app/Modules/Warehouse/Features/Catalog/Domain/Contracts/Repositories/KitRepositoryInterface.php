<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-наборов для Catalog-мутаций.
 */
interface KitRepositoryInterface
{
    /**
     * Возвращает набор по внутреннему идентификатору или null.
     *
     * Шаги:
     * 1. Принять внутренний id комплекта.
     * 2. Вернуть `KitData` или `null`, если запись не найдена.
     */
    public function findById(int $id): ?KitData;

    /**
     * Возвращает набор по import_hash или null.
     *
     * Шаги:
     * 1. Принять import hash комплекта.
     * 2. Вернуть `KitData` или `null`, если запись не найдена.
     */
    public function findByImportHash(string $importHash): ?KitData;

    /**
     * Возвращает существующие ids из переданного списка.
     *
     * @param  list<int>  $ids
     * @return Collection<int, int>
     *
     * Шаги:
     * 1. Принять список внутренних id комплектов.
     * 2. Выбрать из БД только реально существующие id.
     * 3. Вернуть Support Collection с найденными id.
     */
    public function existingIds(array $ids): Collection;

    /**
     * Возвращает ids наборов упаковочного размера.
     *
     * Шаги:
     * 1. Принять id упаковочного размера.
     * 2. Вернуть collection внутренних id комплектов.
     *
     * @return Collection<int, int>
     */
    public function findIdsByPackDimensionId(int $packDimensionId): Collection;
}
