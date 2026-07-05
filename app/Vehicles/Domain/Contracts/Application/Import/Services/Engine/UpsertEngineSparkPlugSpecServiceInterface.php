<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Engine;

use App\Vehicles\Domain\Models\PartSpecification;

interface UpsertEngineSparkPlugSpecServiceInterface
{
    public function upsertByEngine(int $engId, array $details): ?PartSpecification;
}
