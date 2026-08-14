<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\PackDimension;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\PackDimension\PackDimensionImportRowDTO;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;

/**
 * Порт построчного импорта упаковочного размера Warehouse из Excel-строки.
 */
interface ImportPackDimensionFromRowServiceInterface
{
    /**
     * Валидирует строку и пишет упаковочный размер через явные create/update команды.
     *
     * Шаги:
     * 1) Прочитать id, тип и габариты из строки.
     * 2) Нормализовать и провалидировать значения.
     * 3) Найти существующую запись по id.
     * 4) Обновить найденную запись или создать новую.
     */
    public function importFromRow(PackDimensionImportRowDTO $row): PackDimensionData;
}
