<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;

interface UpsertEngineSparkPlugSpecServiceInterface
{
    public function upsertByEngine(int $engId, array $details): ?PartSpecificationData;
}
