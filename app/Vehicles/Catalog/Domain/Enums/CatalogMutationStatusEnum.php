<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Enums;

enum CatalogMutationStatusEnum: string
{
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
