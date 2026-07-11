<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\EngineModification;

use App\Vehicles\Import\Domain\DTOs\EngineModification\EngineModificationCommandRowDTO;

interface LinkEngineModificationFromRowServiceInterface
{
    public function linkFromRow(EngineModificationCommandRowDTO $row): void;
}
