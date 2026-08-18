<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Brand\BrandBulkDeleteResultDTO;

/**
 * Порт публикации результата массового удаления брендов наружу.
 */
interface BrandBulkDeleteNotificationServiceInterface
{
    /**
     * Публикует единый результат bulk-delete брендов во внешний транспорт.
     */
    public function notify(BrandBulkDeleteResultDTO $result): void;
}
