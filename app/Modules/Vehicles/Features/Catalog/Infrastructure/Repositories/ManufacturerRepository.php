<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;

/**
 * Читает производителей через Eloquent-модель фичи Catalog.
 */
final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок производителей по внешнему идентификатору.
     */
    public function findByMfaId(int $mfaId): ?ManufacturerData
    {
        return ManufacturerData::optional(Manufacturer::query()->where('mfa_id', $mfaId)->first());
    }
}
