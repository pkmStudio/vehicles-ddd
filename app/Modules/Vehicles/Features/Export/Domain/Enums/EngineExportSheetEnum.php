<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Enums;

enum EngineExportSheetEnum: string
{
    case Main = 'main';
    case SparkPlug = 'spark_plug';
}
