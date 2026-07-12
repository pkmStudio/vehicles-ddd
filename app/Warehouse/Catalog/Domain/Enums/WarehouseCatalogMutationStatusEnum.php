<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Enums;

/**
 * Перечисляет статусы результата мутации Warehouse-каталога.
 */
enum WarehouseCatalogMutationStatusEnum: string
{
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
