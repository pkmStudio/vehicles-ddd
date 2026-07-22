<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;

/**
 * Порт записи упаковочного размера Warehouse.
 */
interface PackDimensionCommandInterface
{
    /**
     * Обновляет упаковочный размер по id.
     */
    public function updateById(PackDimensionData $data): PackDimensionData;

    /**
     * Создаёт новый упаковочный размер.
     */
    public function create(PackDimensionData $data): PackDimensionData;
}
