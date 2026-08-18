<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Notifications;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleBulkDeleteResultDTO;

/**
 * Порт публикации результата массового удаления автомобилей наружу.
 */
interface VehicleBulkDeleteNotificationServiceInterface
{
    /**
     * Публикует единый результат bulk-delete автомобилей во внешний транспорт.
     */
    public function notify(VehicleBulkDeleteResultDTO $result): void;
}
