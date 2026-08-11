<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;

/**
 * Порт чтения Warehouse-брендов для Catalog-мутаций.
 */
interface BrandRepositoryInterface
{
    /**
     * Возвращает бренд по внутреннему идентификатору или null.
     */
    public function findById(int $id): ?BrandData;

    /**
     * Возвращает бренд по имени или null.
     */
    public function findByName(string $name): ?BrandData;
}
