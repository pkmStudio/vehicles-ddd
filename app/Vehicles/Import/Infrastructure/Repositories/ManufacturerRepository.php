<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\ManufacturerData;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;

final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    public function firstByMfaId(int $mfaId): ?ManufacturerData
    {
        return ManufacturerData::optional(Manufacturer::query()->where('mfa_id', $mfaId)->first());
    }

    public function minMfaId(): int
    {
        return (int) Manufacturer::query()->min('mfa_id');
    }
}
