<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Kit;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;

/**
 * Порт построчного upsert Warehouse-набора (Kit) из Excel-строки.
 */
interface UpsertKitFromRowServiceInterface
{
    /**
     * Резолвит состав по артикулам, считает свойства набора через KitProperties и пишет запись.
     *
     * @param  array<int, mixed>  $row  сырая Excel-строка (0-based индексы)
     *
     * @throws \InvalidArgumentException если артикул не найден или упаковка не определилась
     */
    public function upsertFromRow(array $row): KitData;
}
