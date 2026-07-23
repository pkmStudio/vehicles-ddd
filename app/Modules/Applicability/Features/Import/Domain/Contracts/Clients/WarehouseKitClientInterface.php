<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Clients;

interface WarehouseKitClientInterface
{
    public function exists(int $kitId): bool;
}
