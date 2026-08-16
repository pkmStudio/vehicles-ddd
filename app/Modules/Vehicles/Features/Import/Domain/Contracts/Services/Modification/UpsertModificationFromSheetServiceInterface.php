<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface UpsertModificationFromSheetServiceInterface
{
    /**
     * Создать или обновить модификацию из manager Excel-листа.
     *
     * Шаги:
     * 1) Найти автомобиль по ms_id и рассчитать type.
     * 2) Создать OD-запись или обновить существующую по provider/allow_change_fields правилам.
     *
     * @throws ImportRowValidationException
     * @throws ImportRowReferenceNotFoundException
     */
    public function upsertFromRow(ModificationSheetRowDTO $row): ModificationData;
}
