<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\PackDimensionData;

/**
 * Порт записи упаковочных размеров Warehouse.
 */
interface PackDimensionCommandInterface
{
    /**
     * Создаёт упаковочный размер и возвращает актуальный снимок.
     */
    public function create(PackDimensionData $data): PackDimensionData;

    /**
     * Обновляет упаковочный размер и возвращает актуальный снимок.
     */
    public function update(PackDimensionData $data): PackDimensionData;

    /**
     * Удаляет упаковочный размер по id.
     */
    public function deleteById(int $id): void;
}
