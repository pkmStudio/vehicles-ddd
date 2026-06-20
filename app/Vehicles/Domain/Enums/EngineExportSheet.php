<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Enums;

enum EngineExportSheet: string
{
    case Main = 'main';

    case SparkPlugs = 'spark_plugs';
}
