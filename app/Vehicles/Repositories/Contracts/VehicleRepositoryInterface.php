<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories\Contracts;

use App\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

/**
 * Чтение Vehicle (read-only).
 */
interface VehicleRepositoryInterface
{
    public function find(int $id): ?Vehicle;

    public function findOrFail(int $id): Vehicle;

    public function all(): Collection;

    public function firstWhere(string $column, mixed $value): ?Vehicle;
}
