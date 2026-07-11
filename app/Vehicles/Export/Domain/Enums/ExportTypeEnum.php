<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Enums;

/**
 * Тип каталога, который внешний сервис просит выгрузить через RabbitMQ.
 */
enum ExportTypeEnum: string
{
    case Vehicle = 'vehicle_multi_sheet';
    case Engine = 'engine_multi_sheet';
}
