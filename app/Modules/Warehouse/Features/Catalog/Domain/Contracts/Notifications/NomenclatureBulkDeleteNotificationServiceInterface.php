<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureBulkDeleteResultDTO;

/**
 * Порт публикации результата массового удаления номенклатуры наружу.
 */
interface NomenclatureBulkDeleteNotificationServiceInterface
{
    /**
     * Публикует единый результат bulk-delete номенклатуры во внешний транспорт.
     */
    public function notify(NomenclatureBulkDeleteResultDTO $result): void;
}
