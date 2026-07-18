<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

/**
 * Порт публикации результата мутации Warehouse-каталога наружу.
 */
interface WarehouseCatalogMutationNotificationServiceInterface
{
    /**
     * Публикует результат мутации во внешний транспорт.
     */
    public function notify(WarehouseCatalogMutationResultDTO $result): void;
}
