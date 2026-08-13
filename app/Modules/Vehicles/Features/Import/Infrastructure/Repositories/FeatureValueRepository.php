<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\FeatureValueData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\FeatureValue;

/**
 * Читает feature value snapshots для import-сценариев Vehicles.
 */
final readonly class FeatureValueRepository implements FeatureValueRepositoryInterface
{
    /**
     * Ищет значение характеристики по имени.
     *
     * Шаги:
     * 1) Отфильтровать feature value модель по `name`.
     * 2) Сконвертировать найденную Eloquent-модель в optional `FeatureValueData`.
     */
    public function findByName(string $name): ?FeatureValueData
    {
        return FeatureValueData::optional(FeatureValue::query()->where('name', $name)->first());
    }
}
