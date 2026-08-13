<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Presentation\Console\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Warehouse\Features\Import\Presentation\Console\Support\RequestLocalImportCommand;

/**
 * Ручной/операционный запуск импорта упаковочных размеров Warehouse: тот же RabbitMQ-flow, что и
 * warehouse:request-import-pack-dimensions, но не удаляет исходный файл после импорта (это файл
 * оператора, не одноразовая выгрузка CRM) — см. cleanupAfterImport().
 */
final class ImportPackDimensions extends RequestLocalImportCommand
{
    protected $signature = 'warehouse:import-pack-dimensions
        {path : Относительный путь к файлу на Storage disk}
        {--disk=project_storage : Laravel Storage disk, где лежит файл}
        {--user-id=1 : ID инициатора для отчёта об импорте}
        {--operation-id= : Идемпотентный ID прогона; по умолчанию UUID}';

    protected $description = 'Опубликовать RabbitMQ-запрос импорта упаковочных размеров Warehouse из локального Storage (файл не удаляется после импорта)';

    /**
     * Возвращает имя события запроса импорта упаковочных размеров.
     *
     * Шаги:
     * 1) Выбрать wire event для Warehouse pack dimension import.
     * 2) Передать имя события базовой команде публикации.
     */
    protected function eventName(): string
    {
        return 'WAREHOUSE_PACK_DIMENSION_IMPORT_FILE_REQUESTED';
    }

    /**
     * Возвращает routing key запроса импорта упаковочных размеров.
     *
     * Шаги:
     * 1) Выбрать routing key bindings для pack dimension import.
     * 2) Передать routing key publisher'у через базовую команду.
     */
    protected function routingKey(): string
    {
        return 'crm.warehouse.pack-dimensions.import';
    }

    /**
     * Возвращает тип импорта упаковочных размеров.
     *
     * Шаги:
     * 1) Взять wire-значение ImportTypeEnum::PackDimension.
     * 2) Передать его handler'у для выбора PackDimensionImport adapter.
     */
    protected function importType(): string
    {
        return ImportTypeEnum::PackDimension->value;
    }
}
