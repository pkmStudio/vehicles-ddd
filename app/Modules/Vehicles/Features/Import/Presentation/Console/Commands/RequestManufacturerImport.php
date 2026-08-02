<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Presentation\Console\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Modules\Vehicles\Features\Import\Presentation\Console\Support\RequestLocalImportCommand;

final class RequestManufacturerImport extends RequestLocalImportCommand
{
    protected $signature = 'vehicles:request-import-manufacturers
        {path : Относительный путь к файлу на Storage disk}
        {--disk=local : Laravel Storage disk, где лежит файл}
        {--user-id=1 : ID инициатора для отчёта об импорте}
        {--operation-id= : Идемпотентный ID прогона; по умолчанию UUID}';

    protected $description = 'Опубликовать RabbitMQ-запрос импорта производителей (mfa_id, name, provider) из локального Storage';

    protected function eventName(): string
    {
        return 'MANUFACTURERS_IMPORT_FILE_REQUESTED';
    }

    protected function routingKey(): string
    {
        return 'crm.manufacturers.import';
    }

    protected function importType(): string
    {
        return ExternalImportTypeEnum::Manufacturer->value;
    }
}
