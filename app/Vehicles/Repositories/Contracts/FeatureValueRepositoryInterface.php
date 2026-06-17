<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories\Contracts;

use App\Vehicles\Models\FeatureValue;
use Illuminate\Database\Eloquent\Collection;

/**
 * Чтение FeatureValue (read-only).
 */
interface FeatureValueRepositoryInterface
{
    public function find(int $id): ?FeatureValue;

    public function findOrFail(int $id): FeatureValue;

    public function all(): Collection;

    public function firstWhere(string $column, mixed $value): ?FeatureValue;
}
