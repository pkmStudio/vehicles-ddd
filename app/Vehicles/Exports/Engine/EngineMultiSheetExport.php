<?php

declare(strict_types=1);

namespace App\Vehicles\Exports\Engine;

use App\Vehicles\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Vehicles\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final readonly class EngineMultiSheetExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            app(EngineMainSheetExport::class),
            app(EngineSparkPlugsSheetExport::class),
        ];
    }
}
