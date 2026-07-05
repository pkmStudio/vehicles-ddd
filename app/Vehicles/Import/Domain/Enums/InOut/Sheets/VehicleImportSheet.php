<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Enums\InOut\Sheets;

enum VehicleImportSheet: string
{
    case Main = 'Основная информация';

    case Wipers = 'Дворники';
}
