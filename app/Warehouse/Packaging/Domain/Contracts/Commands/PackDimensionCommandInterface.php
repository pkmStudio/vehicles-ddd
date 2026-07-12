<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Domain\Contracts\Commands;

use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;

/**
 * Порт записи упаковочного размера Warehouse — только создание сгенерированной коробки, когда
 * ни одна из существующих не подошла (см. `AbstractPackagingStrategy::calculatePak()`).
 */
interface PackDimensionCommandInterface
{
    /**
     * Создаёт новый упаковочный размер и возвращает сохранённую запись.
     */
    public function create(PackDimensionData $data): PackDimensionData;
}
