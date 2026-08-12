<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface UpsertModificationFromRowServiceInterface
{
    /**
     * Создать или обновить модификацию из command row.
     *
     * Шаги:
     * 1) Преобразовать row DTO в ModificationData.
     * 2) Найти существующую модификацию по натуральному ключу.
     * 3) Выполнить create/update или вернуть null при невозможности записи.
     */
    public function upsertFromRow(ModificationCommandRowDTO $row): ?ModificationData;
}
