<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums;

enum VehicleExportSheet: string
{
    case Main = 'main';

    case Wipers = 'wipers';
}
