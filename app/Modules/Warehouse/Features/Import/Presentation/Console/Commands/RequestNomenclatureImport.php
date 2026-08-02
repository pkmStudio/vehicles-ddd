<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Presentation\Console\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Warehouse\Features\Import\Presentation\Console\Support\RequestLocalImportCommand;

final class RequestNomenclatureImport extends RequestLocalImportCommand
{
    protected $signature = 'warehouse:request-import-nomenclature
        {path : Относительный путь к файлу на Storage disk}
        {--disk=local : Laravel Storage disk, где лежит файл}
        {--user-id=1 : ID инициатора для отчёта об импорте}
        {--operation-id= : Идемпотентный ID прогона; по умолчанию UUID}';

    protected $description = 'Опубликовать RabbitMQ-запрос импорта Warehouse-номенклатуры из локального Storage';

    protected function eventName(): string
    {
        return 'WAREHOUSE_NOMENCLATURE_IMPORT_FILE_REQUESTED';
    }

    protected function routingKey(): string
    {
        return 'crm.warehouse.nomenclatures.import';
    }

    protected function importType(): string
    {
        return ImportTypeEnum::Nomenclature->value;
    }
}
