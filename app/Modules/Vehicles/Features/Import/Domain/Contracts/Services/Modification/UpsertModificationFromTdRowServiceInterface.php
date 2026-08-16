<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Modification;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface UpsertModificationFromTdRowServiceInterface
{
    /**
     * Создать или обновить модификацию из command row.
     *
     * Шаги:
     * 1) Преобразовать row DTO в ModificationData.
     * 2) Найти существующую модификацию по натуральному ключу.
     * 3) Выполнить create/update или выбросить ошибку строки импорта.
     *
     * @throws ImportRowValidationException
     * @throws ImportRowReferenceNotFoundException
     */
    public function upsertFromRow(ModificationTdRowDTO $row): ModificationData;
}
