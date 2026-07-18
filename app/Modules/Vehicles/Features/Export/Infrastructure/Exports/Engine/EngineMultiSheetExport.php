<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\EngineMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine\Sheets\EngineMainSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Engine\Sheets\EngineSparkPlugsSheetExport;

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
