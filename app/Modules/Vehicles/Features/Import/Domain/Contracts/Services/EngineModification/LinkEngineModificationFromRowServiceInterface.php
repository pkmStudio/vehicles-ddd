<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationCommandRowDTO;

interface LinkEngineModificationFromRowServiceInterface
{
    /**
     * Привязать двигатель к модификации из command row.
     *
     * Шаги:
     * 1) Преобразовать row DTO в ключи engine/modification связи.
     * 2) Делегировать idempotent sync command.
     */
    public function linkFromRow(EngineModificationCommandRowDTO $row): void;
}
