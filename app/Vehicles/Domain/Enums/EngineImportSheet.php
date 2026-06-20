<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums;

enum EngineImportSheet: string
{
    case Main = 'Двигатели';

    case SparkPlugs = 'Свечи зажигания';
}
