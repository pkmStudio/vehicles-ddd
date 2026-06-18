<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories\FeatureValue;

use App\Vehicles\Domain\Models\FeatureValue;
use Illuminate\Database\Eloquent\Collection;

interface FeatureValueRepositoryInterface
{
    public function find(int $id): ?FeatureValue;

    public function findOrFail(int $id): FeatureValue;

    public function all(): Collection;

    public function firstByName(string $name): ?FeatureValue;
}
