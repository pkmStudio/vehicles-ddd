<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\FeatureValueData;

interface FeatureValueRepositoryInterface
{
    public function firstByName(string $name): ?FeatureValueData;
}
