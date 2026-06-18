<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories\Modification;

use App\Vehicles\Domain\Models\Modification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Чтение Modification (read-only).
 */
interface ModificationRepositoryInterface
{
    public function find(int $id): ?Modification;

    public function findOrFail(int $id): Modification;

    public function all(): Collection;

    public function firstWhere(string $column, mixed $value): ?Modification;
}
