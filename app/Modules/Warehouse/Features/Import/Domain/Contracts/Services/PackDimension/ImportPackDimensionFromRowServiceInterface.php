<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\PackDimension;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;

/**
 * Порт построчного импорта упаковочного размера Warehouse из Excel-строки.
 */
interface ImportPackDimensionFromRowServiceInterface
{
    /**
     * Валидирует строку и пишет упаковочный размер через явные create/update команды.
     *
     * @param  array<int, mixed>  $row
     */
    public function importFromRow(array $row): PackDimensionData;
}
