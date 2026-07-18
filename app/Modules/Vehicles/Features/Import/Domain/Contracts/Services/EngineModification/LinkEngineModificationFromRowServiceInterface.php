<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModification;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\EngineModification\EngineModificationCommandRowDTO;

interface LinkEngineModificationFromRowServiceInterface
{
    public function linkFromRow(EngineModificationCommandRowDTO $row): void;
}
