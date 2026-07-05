<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Engine;

use App\Vehicles\Domain\Models\Engine;

interface UpsertEngineFromSheetServiceInterface
{
    public function upsertFromRow(array $row): Engine;
}
