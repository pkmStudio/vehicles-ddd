<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine\EngineSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface UpsertEngineFromSheetServiceInterface
{
    public function upsertFromRow(EngineSheetRowDTO $row): EngineData;
}
