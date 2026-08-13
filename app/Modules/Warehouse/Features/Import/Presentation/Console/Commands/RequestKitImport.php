<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Presentation\Console\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Warehouse\Features\Import\Presentation\Console\Support\RequestLocalImportCommand;

final class RequestKitImport extends RequestLocalImportCommand
{
    protected $signature = 'warehouse:request-import-kits
        {path : Относительный путь к файлу на Storage disk}
        {--disk=project_storage : Laravel Storage disk, где лежит файл}
        {--user-id=1 : ID инициатора для отчёта об импорте}
        {--operation-id= : Идемпотентный ID прогона; по умолчанию UUID}';

    protected $description = 'Опубликовать RabbitMQ-запрос импорта Warehouse-наборов из локального Storage';

    /**
     * Возвращает имя события запроса импорта наборов.
     *
     * Шаги:
     * 1) Выбрать wire event для Warehouse Kit import.
     * 2) Передать имя события базовой команде публикации.
     */
    protected function eventName(): string
    {
        return 'WAREHOUSE_KIT_IMPORT_FILE_REQUESTED';
    }

    /**
     * Возвращает routing key запроса импорта наборов.
     *
     * Шаги:
     * 1) Выбрать routing key bindings для kit import.
     * 2) Передать routing key publisher'у через базовую команду.
     */
    protected function routingKey(): string
    {
        return 'crm.warehouse.kits.import';
    }

    /**
     * Возвращает тип импорта наборов.
     *
     * Шаги:
     * 1) Взять wire-значение ImportTypeEnum::Kit.
     * 2) Передать его handler'у для выбора KitImport adapter.
     */
    protected function importType(): string
    {
        return ImportTypeEnum::Kit->value;
    }
}
