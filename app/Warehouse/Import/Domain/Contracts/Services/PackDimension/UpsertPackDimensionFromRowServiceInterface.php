<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Services\PackDimension;

use App\Warehouse\Import\Domain\ModelData\PackDimensionData;

/**
 * Порт построчного upsert упаковочного размера Warehouse из Excel-строки.
 */
interface UpsertPackDimensionFromRowServiceInterface
{
    /**
     * Валидирует строку и пишет запись через Command.
     *
     * @param  array<int, mixed>  $row  сырая Excel-строка (0-based индексы)
     *
     * @throws \InvalidArgumentException при нарушении бизнес-правил валидации
     */
    public function upsertFromRow(array $row): PackDimensionData;
}
