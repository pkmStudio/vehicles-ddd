<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Repositories;

use App\Vehicles\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\ModelData\PartSpecificationData;
use App\Vehicles\Catalog\Infrastructure\Models\PartSpecification;

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
