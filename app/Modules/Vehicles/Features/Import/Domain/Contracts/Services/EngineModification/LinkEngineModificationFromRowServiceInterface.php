<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;

interface LinkEngineModificationFromRowServiceInterface
{
    /**
     * Привязать двигатель к модификации из row DTO.
     *
     * Шаги:
     * 1) Преобразовать row DTO в ключи engine/modification связи.
     * 2) Проверить, что обе связанные сущности уже существуют.
     * 3) Делегировать idempotent sync command.
     *
     * @throws ImportRowValidationException
     * @throws ImportRowReferenceNotFoundException
     */
    public function linkFromRow(EngineModificationRowDTO $row): void;
}
