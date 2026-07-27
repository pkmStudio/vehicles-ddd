<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Presentation\Console\Commands;

use App\Modules\Shared\Presentation\Console\Commands\RequestLocalImportCommand;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;

final class RequestPackDimensionImport extends RequestLocalImportCommand
{
    protected $signature = 'warehouse:request-import-pack-dimensions
        {path : Относительный путь к файлу на Storage disk}
        {--disk=local : Laravel Storage disk, где лежит файл}
        {--user-id=1 : ID инициатора для отчёта об импорте}
        {--run-id= : Идемпотентный ID прогона; по умолчанию UUID}';

    protected $description = 'Опубликовать RabbitMQ-запрос импорта упаковочных размеров Warehouse из локального Storage';

    protected function eventName(): string
    {
        return 'WAREHOUSE_PACK_DIMENSION_IMPORT_FILE_REQUESTED';
    }

    protected function routingKey(): string
    {
        return 'crm.warehouse.pack-dimensions.import';
    }

    protected function importType(): string
    {
        return ImportTypeEnum::PackDimension->value;
    }
}
