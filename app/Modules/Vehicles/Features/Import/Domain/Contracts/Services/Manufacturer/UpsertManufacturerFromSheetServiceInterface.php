<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

interface UpsertManufacturerFromSheetServiceInterface
{
    /**
     * Создать или обновить производителя из Excel row.
     *
     * Шаги:
     * 1) Преобразовать sheet row DTO в ManufacturerData.
     * 2) Найти существующего производителя по mfa_id.
     * 3) Выполнить create/update и вернуть актуальный snapshot.
     */
    public function upsertFromRow(ManufacturerSheetRowDTO $row): ManufacturerData;
}
