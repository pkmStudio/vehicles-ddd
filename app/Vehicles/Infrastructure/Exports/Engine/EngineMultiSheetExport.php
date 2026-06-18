<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Engine;

use App\Vehicles\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Vehicles\Infrastructure\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;
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
