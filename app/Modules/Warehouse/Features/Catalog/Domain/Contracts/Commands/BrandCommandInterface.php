<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\BrandData;

/**
 * Порт записи Warehouse-брендов.
 */
interface BrandCommandInterface
{
    /**
     * Создаёт бренд и возвращает актуальный снимок.
     */
    public function create(BrandData $data): BrandData;

    /**
     * Обновляет бренд и возвращает актуальный снимок.
     */
    public function update(BrandData $data): BrandData;

    /**
     * Удаляет бренд по id.
     */
    public function deleteById(int $id): void;
}
