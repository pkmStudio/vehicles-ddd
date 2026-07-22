<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\FeatureValueData;

interface FeatureValueRepositoryInterface
{
    public function findByName(string $name): ?FeatureValueData;
}
