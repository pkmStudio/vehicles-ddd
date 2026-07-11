<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Engine;

use App\Vehicles\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Vehicles\Import\Domain\ModelData\EngineData;

interface UpsertEngineFromSheetServiceInterface
{
    public function upsertFromRow(EngineSheetRowDTO $row): EngineData;
}
