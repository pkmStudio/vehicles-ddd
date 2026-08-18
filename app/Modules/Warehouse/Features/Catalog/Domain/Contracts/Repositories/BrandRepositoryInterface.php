<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-брендов для Catalog-мутаций.
 */
interface BrandRepositoryInterface
{
    /**
     * Возвращает бренд по внутреннему идентификатору или null.
     *
     * Шаги:
     * 1. Принять внутренний id бренда.
     * 2. Вернуть `BrandData` или `null`, если запись не найдена.
     */
    public function findById(int $id): ?BrandData;

    /**
     * Возвращает бренд по имени или null.
     *
     * Шаги:
     * 1. Принять точное имя бренда.
     * 2. Вернуть `BrandData` или `null`, если запись не найдена.
     */
    public function findByName(string $name): ?BrandData;

    /**
     * Возвращает бренды по id, индексированные по id.
     *
     * Шаги:
     * 1. Принять список внутренних id брендов.
     * 2. Вернуть найденные `BrandData`, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, BrandData>
     */
    public function findByIds(array $ids): Collection;
}
