<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

interface ManufacturerRepositoryInterface
{
    public function firstByName(string $name): ?ManufacturerData;

    public function firstByMfaId(int $mfaId): ?ManufacturerData;

    /** Минимальный mfa_id (для генерации отрицательных id новых марок). 0 если таблица пуста. */
    public function minMfaId(): int;
}
