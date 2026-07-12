<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Enums;

/**
 * Перечисляет операции точечных мутаций Warehouse-каталога.
 */
enum WarehouseCatalogMutationOperationEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
