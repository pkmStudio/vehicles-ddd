<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\Manufacturer\ManufacturerData;
use Illuminate\Support\Collection;

interface ManufacturerRepositoryInterface
{
    public function find(int $id): ?ManufacturerData;

    public function findOrFail(int $id): ManufacturerData;

    /** @return Collection<int, ManufacturerData> */
    public function all(): Collection;

    public function firstByMfaId(int $mfaId): ?ManufacturerData;

    /** Минимальный mfa_id (для генерации отрицательных id новых марок). 0 если таблица пуста. */
    public function minMfaId(): int;
}
