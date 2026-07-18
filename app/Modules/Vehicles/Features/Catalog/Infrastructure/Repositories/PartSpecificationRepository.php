<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;

/**
 * Читает спецификации деталей через Eloquent-модель фичи Catalog.
 */
final readonly class PartSpecificationRepository implements PartSpecificationRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок спецификаций деталей по внешнему идентификатору.
     */
    public function firstById(int $id): ?PartSpecificationData
    {
        return PartSpecificationData::optional(PartSpecification::query()->where('id', $id)->first());
    }
}
