<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Clients;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Import\Infrastructure\Models\Kit;

final readonly class WarehouseKitClient implements WarehouseKitClientInterface
{
    public function exists(int $kitId): bool
    {
        return Kit::query()->whereKey($kitId)->exists();
    }
}
