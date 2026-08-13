<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\FeatureValueData;

interface FeatureValueRepositoryInterface
{
    /**
     * Найти значение фичи по имени.
     *
     * Шаги:
     * 1) Выполнить read query по name.
     * 2) Вернуть FeatureValueData или null.
     */
    public function findByName(string $name): ?FeatureValueData;
}
