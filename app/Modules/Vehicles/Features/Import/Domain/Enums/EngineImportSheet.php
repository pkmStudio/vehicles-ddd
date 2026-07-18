<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Enums;

enum EngineImportSheet: string
{
    case Main = 'Двигатели';

    case SparkPlugs = 'Свечи зажигания';
}
