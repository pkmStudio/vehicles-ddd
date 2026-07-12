<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Services;

use App\Warehouse\Catalog\Domain\DTOs\WarehouseCatalogMutationResultDTO;

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
