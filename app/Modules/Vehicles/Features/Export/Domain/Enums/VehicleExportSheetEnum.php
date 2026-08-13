<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Enums;

enum VehicleExportSheetEnum: string
{
    case Main = 'main';
    case Wiper = 'wiper';
}
