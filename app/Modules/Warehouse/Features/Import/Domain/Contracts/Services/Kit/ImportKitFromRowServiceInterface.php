<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Kit;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;

/**
 * Порт построчного импорта Warehouse-набора из Excel-строки.
 */
interface ImportKitFromRowServiceInterface
{
    /**
     * Валидирует строку и пишет набор через явные create/update команды.
     *
     * @param  array<int, mixed>  $row
     */
    public function importFromRow(array $row): KitData;
}
