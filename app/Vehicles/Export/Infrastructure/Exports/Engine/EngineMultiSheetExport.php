<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Exports\Engine;

use App\Vehicles\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Vehicles\Export\Domain\Enums\ExportTypeEnum;
use App\Vehicles\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Vehicles\Export\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Vehicles\Export\Infrastructure\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;

final readonly class EngineMultiSheetExport extends AbstractMultiSheetExport implements EngineMultiSheetExportInterface
{
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::Engine;
    }

    public function sheets(): array
    {
        return [
            app(EngineMainSheetExport::class),
            app(EngineSparkPlugsSheetExport::class),
        ];
    }
}
