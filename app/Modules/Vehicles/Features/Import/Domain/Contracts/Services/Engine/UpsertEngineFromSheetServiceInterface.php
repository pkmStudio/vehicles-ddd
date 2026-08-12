<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface UpsertEngineFromSheetServiceInterface
{
    /**
     * Создать или обновить двигатель из строки Excel-листа.
     *
     * Шаги:
     * 1) Преобразовать row DTO в EngineData.
     * 2) Найти существующий двигатель по натуральному ключу.
     * 3) Выполнить create/update и вернуть актуальный snapshot.
     */
    public function upsertFromRow(EngineSheetRowDTO $row): EngineData;
}
