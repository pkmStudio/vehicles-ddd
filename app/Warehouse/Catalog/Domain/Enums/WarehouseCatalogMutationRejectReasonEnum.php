<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Enums;

/**
 * Перечисляет бизнес-причины отклонения мутации Warehouse-каталога.
 */
enum WarehouseCatalogMutationRejectReasonEnum: string
{
    case AlreadyExists = 'already_exists';
    case NotFound = 'not_found';
    case BrandNotFound = 'brand_not_found';
    case TypeNotFound = 'type_not_found';
    case NomenclatureNotFound = 'nomenclature_not_found';
    case DeleteBlocked = 'delete_blocked';
    case InvalidComposition = 'invalid_composition';
    case PackDimensionNotResolvable = 'pack_dimension_not_resolvable';
}
