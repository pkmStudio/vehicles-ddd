<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications;

/**
 * Порт публикации результата bulk-сброса Warehouse-наборов.
 */
interface KitResetNotificationServiceInterface
{
    /**
     * Публикует успешное завершение сброса наборов.
     */
    public function completed(int $userId, string $operationId): void;

    /**
     * Публикует технический сбой сброса наборов.
     */
    public function failed(int $userId, string $operationId): void;
}
