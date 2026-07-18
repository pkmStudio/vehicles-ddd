<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Enums;

/**
 * Тип импортного адаптера, который внешний сервис просит запустить через RabbitMQ.
 */
enum ExternalImportTypeEnum: string
{
    case VehicleMultiSheet = 'vehicle_multi_sheet';
    case EngineMultiSheet = 'engine_multi_sheet';
    case EngineCross = 'engine_cross';
    case EngineSparkPlugsByModification = 'engine_spark_plugs_by_modification';
}
