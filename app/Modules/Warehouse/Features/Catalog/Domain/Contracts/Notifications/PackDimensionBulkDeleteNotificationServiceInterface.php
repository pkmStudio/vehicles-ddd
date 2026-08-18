<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\PackDimension\PackDimensionBulkDeleteResultDTO;

/**
 * Порт публикации результата массового удаления упаковок наружу.
 */
interface PackDimensionBulkDeleteNotificationServiceInterface
{
    /**
     * Публикует единый результат bulk-delete упаковок во внешний транспорт.
     */
    public function notify(PackDimensionBulkDeleteResultDTO $result): void;
}
