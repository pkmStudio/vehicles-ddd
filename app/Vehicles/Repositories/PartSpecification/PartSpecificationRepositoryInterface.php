<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories\PartSpecification;

use App\Vehicles\Models\PartSpecification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Чтение PartSpecification (read-only).
 */
interface PartSpecificationRepositoryInterface
{
    public function find(int $id): ?PartSpecification;

    public function findOrFail(int $id): PartSpecification;

    public function all(): Collection;

    public function firstWhere(string $column, mixed $value): ?PartSpecification;
}
