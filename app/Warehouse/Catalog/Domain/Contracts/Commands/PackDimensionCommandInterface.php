<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Commands;

use App\Warehouse\Catalog\Domain\ModelData\PackDimensionData;

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
     * Удаляет упаковочный размер по id без каскада.
     */
    public function deleteById(int $id): void;
}
