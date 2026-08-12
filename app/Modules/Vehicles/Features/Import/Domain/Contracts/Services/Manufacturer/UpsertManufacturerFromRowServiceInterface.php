<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

interface UpsertManufacturerFromRowServiceInterface
{
    /**
     * Создать или обновить производителя из command row.
     *
     * Шаги:
     * 1) Преобразовать row DTO в ManufacturerData.
     * 2) Найти существующего производителя по натуральному ключу.
     * 3) Выполнить create/update и вернуть актуальный snapshot.
     */
    public function upsertFromRow(ManufacturerCommandRowDTO $row): ManufacturerData;
}
