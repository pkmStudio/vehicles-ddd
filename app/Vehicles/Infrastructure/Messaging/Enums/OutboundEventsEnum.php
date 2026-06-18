<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Messaging\Enums;

/**
 * Перечисление исходящих событий, которые отправляются из dan-vehicles.
 * Значение enum используется как routing key для RabbitMQ.
 *
 * event => routing key
 *
 * TODO: дообъявить доменные события vehicles по мере необходимости, напр.:
 *   case VEHICLE_UPSERTED = 'vehicles.vehicle.upserted';
 * TODO: добавить события уведомлений сервису с Filament:
 *   IMPORT_SUCCEEDED (импорт без ошибок) и IMPORT_FAILED (ошибка обработки) —
 *   см. App\Vehicles\Application\Listeners\ReportImportResultListener.
 */
enum OutboundEventsEnum: string
{
    /** Файл сформирован и сохранён в общем хранилище (S3); сервис с Filament уведомляет пользователя. */
    case FILE_EXPORTED = 'vehicles.file.exported';
}
