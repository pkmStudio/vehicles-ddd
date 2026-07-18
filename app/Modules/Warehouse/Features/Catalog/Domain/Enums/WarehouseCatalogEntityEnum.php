<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Enums;

/**
 * Перечисляет сущности Warehouse-каталога, доступные для внешних мутаций.
 */
enum WarehouseCatalogEntityEnum: string
{
    case Brand = 'brand';
    case Nomenclature = 'nomenclature';
    case PackDimension = 'pack_dimension';
    case Kit = 'kit';
}
