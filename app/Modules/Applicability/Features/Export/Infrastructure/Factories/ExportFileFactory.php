<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Factories;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;

final readonly class ExportFileFactory implements ExportFileFactoryInterface
{
    public function make(ExportTypeEnum $type): FileExportInterface
    {
        return match ($type) {
            ExportTypeEnum::VehicleKitApplicability => app(VehicleKitApplicabilityExportInterface::class),
        };
    }
}
