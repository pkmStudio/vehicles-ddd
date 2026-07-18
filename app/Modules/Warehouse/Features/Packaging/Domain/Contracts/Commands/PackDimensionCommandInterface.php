<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;

/**
 * Порт записи упаковочного размера Warehouse — только создание сгенерированной коробки, когда
 * ни одна из существующих не подошла (см. `AbstractPackagingStrategy::calculatePackDimension()`).
 */
interface PackDimensionCommandInterface
{
    /**
     * Создаёт новый упаковочный размер и возвращает сохранённую запись.
     */
    public function create(PackDimensionData $data): PackDimensionData;
}
