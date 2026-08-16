<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Kit;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\Kit\KitImportRowDTO;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;

/**
 * Порт построчного импорта Warehouse-набора из Excel-строки.
 */
interface ImportKitFromRowServiceInterface
{
    /**
     * Валидирует строку и пишет набор через явные create/update команды.
     *
     * Шаги:
     * 1) Распарсить id набора и список артикулов номенклатур.
     * 2) Найти номенклатуры и проверить, что все артикулы существуют.
     * 3) Собрать свойства набора и импортный hash.
     * 4) Обновить существующий набор или создать новый.
     */
    public function importFromRow(KitImportRowDTO $row): KitData;
}
