<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Engine;

use App\Vehicles\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Vehicles\Infrastructure\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class EngineMultiSheetExport implements EngineMultiSheetExportInterface, WithMultipleSheets
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
