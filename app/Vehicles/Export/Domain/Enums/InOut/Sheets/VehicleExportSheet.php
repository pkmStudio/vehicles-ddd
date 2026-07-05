<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Enums\InOut\Sheets;

enum VehicleExportSheet: string
{
    case Main = 'main';

    case Wipers = 'wipers';
}
