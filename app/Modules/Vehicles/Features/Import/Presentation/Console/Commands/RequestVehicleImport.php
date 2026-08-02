<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Presentation\Console\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Modules\Vehicles\Features\Import\Presentation\Console\Support\RequestLocalImportCommand;

final class RequestVehicleImport extends RequestLocalImportCommand
{
    protected $signature = 'vehicles:request-import-vehicles
        {path : Относительный путь к файлу на Storage disk}
        {--disk=local : Laravel Storage disk, где лежит файл}
        {--user-id=1 : ID инициатора для отчёта об импорте}
        {--operation-id= : Идемпотентный ID прогона; по умолчанию UUID}';

    protected $description = 'Опубликовать RabbitMQ-запрос импорта vehicles + part specifications из локального Storage';

    protected function eventName(): string
    {
        return 'VEHICLES_IMPORT_FILE_REQUESTED';
    }

    protected function routingKey(): string
    {
        return 'crm.vehicles.import';
    }

    protected function importType(): string
    {
        return ExternalImportTypeEnum::VehicleMultiSheet->value;
    }
}
