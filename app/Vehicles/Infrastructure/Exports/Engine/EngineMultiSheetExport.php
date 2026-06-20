<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Exports\Engine;

use App\Vehicles\Domain\DTOs\EngineExportPlan;
use App\Vehicles\Domain\Enums\InOut\Sheets\EngineExportSheet;
use App\Vehicles\Domain\Contracts\Infrastructure\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Vehicles\Infrastructure\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class EngineMultiSheetExport implements EngineMultiSheetExportInterface, WithMultipleSheets
{
    private EngineExportPlan $plan;

    public function __construct(
        ?EngineExportPlan $plan = null,
    ) {
        $this->plan = $plan ?? EngineExportPlan::all();
    }

    public function download(string $fileName): BinaryFileResponse
    {
        return Excel::download($this, $fileName);
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->plan->hasSheet(EngineExportSheet::Main)) {
            $sheets[] = app(EngineMainSheetExport::class);
        }

        if ($this->plan->hasSheet(EngineExportSheet::SparkPlugs)) {
            $sheets[] = app(EngineSparkPlugsSheetExport::class);
        }

        return $sheets;
    }
}
