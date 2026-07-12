<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Commands;

use App\Warehouse\Import\Domain\ModelData\PackDimensionData;

/**
 * Порт записи упаковочного размера Warehouse.
 */
interface PackDimensionCommandInterface
{
    /**
     * Обновляет запись по id, если она существует, иначе создаёт новую.
     */
    public function upsertById(PackDimensionData $data): PackDimensionData;
}
