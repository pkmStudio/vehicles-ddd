<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\EngineExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\ReferenceSheetExport;

final readonly class EngineMultiSheetExport extends AbstractMultiSheetExport implements EngineMultiSheetExportInterface
{
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::Engine;
    }

    public function sheets(): array
    {
        $exportService = app(EngineExportServiceInterface::class);

        return [
            app(EngineMainSheetExport::class),
            app(EngineSparkPlugsSheetExport::class),
            new ReferenceSheetExport(
                headings: $exportService->getReferenceHeadings(),
                rows: $exportService->getReferenceRows(),
            ),
        ];
    }
}
