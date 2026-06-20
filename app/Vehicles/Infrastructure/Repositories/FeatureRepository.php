<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories;

use App\Vehicles\Domain\Contracts\Repositories\FeatureRepositoryInterface;
use App\Vehicles\Domain\Models\Feature;
use Illuminate\Database\Eloquent\Collection;

final readonly class FeatureRepository implements FeatureRepositoryInterface
{
    public function find(int $id): ?Feature
    {
        return Feature::query()->find($id);
    }

    public function findOrFail(int $id): Feature
    {
        return Feature::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return Feature::query()->get();
    }
}
