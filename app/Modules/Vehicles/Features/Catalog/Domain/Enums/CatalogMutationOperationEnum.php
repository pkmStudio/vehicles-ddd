<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Enums;

/**
 * Перечисляет допустимые значения потока мутаций каталога.
 */
enum CatalogMutationOperationEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
