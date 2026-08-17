<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Factories;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\ModificationKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;

final readonly class ExportFileFactory implements ExportFileFactoryInterface
{
    /**
     * Резолвит конкретный Excel export adapter по типу файла.
     *
     * Шаги:
     * 1. Сопоставляет `VehicleKitApplicability` с marker interface его export adapter-а.
     * 2. Сопоставляет `ModificationKitApplicability` с marker interface его export adapter-а.
     * 3. Возвращает adapter из Laravel container через общий `FileExportInterface`.
     */
    public function make(ExportTypeEnum $type): FileExportInterface
    {
        return match ($type) {
            ExportTypeEnum::VehicleKitApplicability => app(VehicleKitApplicabilityExportInterface::class),
            ExportTypeEnum::ModificationKitApplicability => app(ModificationKitApplicabilityExportInterface::class),
        };
    }
}
