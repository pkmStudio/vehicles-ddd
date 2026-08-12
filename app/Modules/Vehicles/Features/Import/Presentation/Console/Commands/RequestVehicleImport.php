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

    /**
     * Возвращает имя inbound-события для импорта vehicles workbook.
     *
     * Шаги:
     * 1. Выбрать event name, который слушает vehicles import Rabbit handler.
     * 2. Вернуть строку для базовой команды публикации.
     */
    protected function eventName(): string
    {
        return 'VEHICLES_IMPORT_FILE_REQUESTED';
    }

    /**
     * Возвращает routing key для запроса импорта vehicles workbook.
     *
     * Шаги:
     * 1. Выбрать binding key из rabbit-transport setup для vehicles import.
     * 2. Вернуть значение для publication envelope.
     */
    protected function routingKey(): string
    {
        return 'crm.vehicles.import';
    }

    /**
     * Возвращает тип import adapter для multi-sheet vehicles workbook.
     *
     * Шаги:
     * 1. Выбрать enum external import type для vehicle multi-sheet файла.
     * 2. Вернуть scalar value для data.import_type.
     */
    protected function importType(): string
    {
        return ExternalImportTypeEnum::VehicleMultiSheet->value;
    }
}
