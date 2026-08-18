<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Notifications;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\EngineBulkDeleteResultDTO;

/**
 * Порт публикации результата массового удаления двигателей наружу.
 */
interface EngineBulkDeleteNotificationServiceInterface
{
    /**
     * Публикует единый результат bulk-delete двигателей во внешний транспорт.
     */
    public function notify(EngineBulkDeleteResultDTO $result): void;
}
