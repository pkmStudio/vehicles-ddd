<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Enums;

enum ExportTypeEnum: string
{
    case VehicleKitApplicability = 'vehicle_kit_applicability';
    case KitApplicability = 'kit_applicability';
}
