<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;

/**
 * Описывает порт чтения спецификаций деталей из каталога.
 */
interface PartSpecificationRepositoryInterface
{
    /**
     * Возвращает Data-снимок спецификации детали по id.
     */
    public function findById(int $id): ?PartSpecificationData;
}
