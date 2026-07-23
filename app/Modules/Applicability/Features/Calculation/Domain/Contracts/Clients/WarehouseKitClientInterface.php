<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface WarehouseKitClientInterface
{
    /** @return iterable<int, KitData> */
    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable;
}
