<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface UpsertEngineFromRowServiceInterface
{
    /**
     * Создать или обновить двигатель из typed import row DTO.
     *
     * Шаги:
     * 1) Дозаполнить eng_id, если row DTO разрешает генерацию отрицательного идентификатора.
     * 2) Преобразовать row DTO в EngineData через factory.
     * 3) Выполнить create/update через command и вернуть актуальный snapshot.
     *
     * @throws ImportRowValidationException
     */
    public function upsertFromRow(EngineSheetRowDTO $row): EngineData;
}
