<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Kit\KitBulkDeleteResultDTO;

/**
 * Порт публикации результата массового удаления наборов наружу.
 */
interface KitBulkDeleteNotificationServiceInterface
{
    /**
     * Публикует единый результат bulk-delete наборов во внешний транспорт.
     *
     * Шаги:
     * 1) Принять типизированный DTO результата массового удаления наборов.
     * 2) Передать результат во внешний notification adapter.
     * 3) Завершить без дополнительного результата.
     */
    public function notify(KitBulkDeleteResultDTO $result): void;
}
