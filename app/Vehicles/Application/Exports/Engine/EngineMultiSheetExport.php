<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Exports\Engine;

use App\Vehicles\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Vehicles\Application\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Vehicles\Application\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final readonly class EngineMultiSheetExport implements WithMultipleSheets, EngineMultiSheetExportInterface
{
    public function download(string $fileName): BinaryFileResponse
    {
        return Excel::download($this, $fileName);
    }

    public function sheets(): array
    {
        return [
            app(EngineMainSheetExport::class),
            app(EngineSparkPlugsSheetExport::class),
        ];
    }
}
