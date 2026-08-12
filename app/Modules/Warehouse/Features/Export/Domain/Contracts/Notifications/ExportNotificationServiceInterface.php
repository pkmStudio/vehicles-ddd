<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Notifications;

use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;

/**
 * Порт исходящего уведомления о завершении Warehouse-экспорта.
 */
interface ExportNotificationServiceInterface
{
    /**
     * Публикует итоговый статус экспорта наружу.
     *
     * Шаги:
     * 1) Принять DTO итогового статуса Warehouse-экспорта.
     * 2) Преобразовать DTO в outbound wire payload на стороне реализации.
     * 3) Отправить уведомление внешнему потребителю.
     */
    public function notifyExportCompleted(ExportCompletionNotificationDTO $payload): void;
}
