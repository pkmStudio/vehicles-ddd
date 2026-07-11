<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Engine;

use App\Vehicles\Import\Domain\ModelData\PartSpecificationData;

interface UpsertEngineSparkPlugSpecServiceInterface
{
    public function upsertByEngine(int $engId, array $details): ?PartSpecificationData;
}
