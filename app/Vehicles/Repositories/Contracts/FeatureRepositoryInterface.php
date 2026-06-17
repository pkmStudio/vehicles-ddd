<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories\Contracts;

use App\Vehicles\Models\Feature;
use Illuminate\Database\Eloquent\Collection;

/**
 * Чтение Feature (read-only).
 */
interface FeatureRepositoryInterface
{
    public function find(int $id): ?Feature;

    public function findOrFail(int $id): Feature;

    public function all(): Collection;

    public function firstWhere(string $column, mixed $value): ?Feature;
}
