<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Enums;

enum VehicleMutationOperationEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
