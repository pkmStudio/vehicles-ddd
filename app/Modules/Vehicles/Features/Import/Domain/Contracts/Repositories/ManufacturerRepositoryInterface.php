<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

interface ManufacturerRepositoryInterface
{
    public function findByName(string $name): ?ManufacturerData;

    public function findByMfaId(int $mfaId): ?ManufacturerData;

    /** Производитель с минимальным mfa_id (для генерации отрицательных id новых марок). */
    public function findMinMfaId(): ?ManufacturerData;
}
