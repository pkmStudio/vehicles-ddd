<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Enums;

enum CatalogMutationOperationEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
