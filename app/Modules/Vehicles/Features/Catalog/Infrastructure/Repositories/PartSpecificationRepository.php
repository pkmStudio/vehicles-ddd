<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Support\Collection;

/**
 * Читает спецификации деталей через Eloquent-модель фичи Catalog.
 */
final readonly class PartSpecificationRepository implements PartSpecificationRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок спецификаций деталей по внешнему идентификатору.
     *
     * Шаги:
     * 1. Фильтрует PartSpecification по id.
     * 2. Берет первую найденную запись.
     * 3. Преобразует модель в `PartSpecificationData` или возвращает `null`.
     */
    public function findById(int $id): ?PartSpecificationData
    {
        return PartSpecificationData::optional(PartSpecification::query()->where('id', $id)->first());
    }

    /**
     * Возвращает ids спецификаций по владельцу.
     *
     * Шаги:
     * 1. Возвращает пустую collection для пустого списка owner ids.
     * 2. Фильтрует спецификации по типу и id владельца.
     * 3. Возвращает найденные ids.
     *
     * @param  array<int, int>  $partableIds
     * @return Collection<int, int>
     */
    public function findIdsByPartable(PartableTypeEnum $partableType, array $partableIds): Collection
    {
        if ($partableIds === []) {
            return collect();
        }

        return PartSpecification::query()
            ->where('partable_type', $partableType->value)
            ->whereIn('partable_id', $partableIds)
            ->pluck('id')
            ->values();
    }
}
