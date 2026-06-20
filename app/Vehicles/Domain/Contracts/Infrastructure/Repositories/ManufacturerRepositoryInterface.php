<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Repositories;

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

    /** Минимальный mfa_id (для генерации отрицательных id новых марок). 0 если таблица пуста. */
    public function minMfaId(): int;
}
