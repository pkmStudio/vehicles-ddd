<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Repositories;

use App\Vehicles\Catalog\Domain\ModelData\PartSpecificationData;

/**
 * Описывает порт чтения спецификаций деталей из каталога.
 */
interface PartSpecificationRepositoryInterface
{
    /**
     * Возвращает Data-снимок спецификации детали по id.
     */
    public function firstById(int $id): ?PartSpecificationData;
}
