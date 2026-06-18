<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories\Manufacturer;

use App\Vehicles\Domain\Models\Manufacturer;
use Illuminate\Database\Eloquent\Collection;

/**
 * Чтение Manufacturer (read-only).
 */
interface ManufacturerRepositoryInterface
{
    public function find(int $id): ?Manufacturer;

    public function findOrFail(int $id): Manufacturer;

    public function all(): Collection;

    public function firstByMfaId(int $mfaId): ?Manufacturer;
}
