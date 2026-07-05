<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\Feature\FeatureData;
use Illuminate\Support\Collection;

interface FeatureRepositoryInterface
{
    public function find(int $id): ?FeatureData;

    public function findOrFail(int $id): FeatureData;

    /** @return Collection<int, FeatureData> */
    public function all(): Collection;
}
