<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\UseCases\Engine;

use App\Vehicles\Domain\Models\PartSpecification;

interface UpsertEngineSparkPlugSpecUseCaseInterface
{
    public function execute(int $engId, array $details): ?PartSpecification;
}
