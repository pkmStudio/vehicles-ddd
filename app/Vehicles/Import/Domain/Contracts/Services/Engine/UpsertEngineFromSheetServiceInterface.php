<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Engine;

use App\Vehicles\Import\Domain\ModelData\Engine\EngineData;

interface UpsertEngineFromSheetServiceInterface
{
    public function upsertFromRow(array $row): EngineData;
}
