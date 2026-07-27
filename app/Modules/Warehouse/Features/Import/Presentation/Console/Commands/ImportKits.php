<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Presentation\Console\Commands;

use App\Modules\Shared\Presentation\Console\Commands\RequestLocalImportCommand;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;

/**
 * Ручной/операционный запуск импорта Warehouse-наборов (Kit): тот же RabbitMQ-flow, что и
 * warehouse:request-import-kits, но не удаляет исходный файл после импорта (это файл оператора,
 * не одноразовая выгрузка CRM) — см. cleanupAfterImport().
 */
final class ImportKits extends RequestLocalImportCommand
{
    protected $signature = 'warehouse:import-kits
        {path : Относительный путь к файлу на Storage disk}
        {--disk=local : Laravel Storage disk, где лежит файл}
        {--user-id=1 : ID инициатора для отчёта об импорте}
        {--run-id= : Идемпотентный ID прогона; по умолчанию UUID}';

    protected $description = 'Опубликовать RabbitMQ-запрос импорта Warehouse-наборов из локального Storage (файл не удаляется после импорта)';

    protected function eventName(): string
    {
        return 'WAREHOUSE_KIT_IMPORT_FILE_REQUESTED';
    }

    protected function routingKey(): string
    {
        return 'crm.warehouse.kits.import';
    }

    protected function importType(): string
    {
        return ImportTypeEnum::Kit->value;
    }
}
