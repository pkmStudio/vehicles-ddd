<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Repositories;

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

    /** Модификация по натуральному ключу (ms_id + mod_id), имеющая двигатели, с загруженными engines. */
    public function firstByMsIdAndModIdWithEngines(int $msId, int $modId): ?Modification;
}
