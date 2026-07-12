<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Repositories;

use App\Vehicles\Catalog\Domain\ModelData\ModificationData;

interface ModificationRepositoryInterface
{
    public function firstByModIdAndType(int $modId, string $type): ?ModificationData;

    public function engineModificationCountByModIdAndType(int $modId, string $type): ?int;
}
