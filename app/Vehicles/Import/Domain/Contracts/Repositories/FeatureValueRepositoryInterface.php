<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\FeatureValue\FeatureValueData;
use Illuminate\Support\Collection;

interface FeatureValueRepositoryInterface
{
    public function find(int $id): ?FeatureValueData;

    public function findOrFail(int $id): FeatureValueData;

    /** @return Collection<int, FeatureValueData> */
    public function all(): Collection;

    public function firstByName(string $name): ?FeatureValueData;
}
