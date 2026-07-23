<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Exports;

use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;

interface VehicleKitApplicabilityExportInterface
{
    public function export(ExportRunContextDTO $context, ?string $disk = null): string;
}
