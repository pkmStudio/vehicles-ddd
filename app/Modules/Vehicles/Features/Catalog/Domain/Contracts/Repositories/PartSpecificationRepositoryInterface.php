<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Support\Collection;

/**
 * Описывает порт чтения спецификаций деталей из каталога.
 */
interface PartSpecificationRepositoryInterface
{
    /**
     * Возвращает Data-снимок спецификации детали по id.
     */
    public function findById(int $id): ?PartSpecificationData;

    /**
     * Возвращает ids спецификаций по владельцу.
     *
     * @param  array<int, int>  $partableIds
     * @return Collection<int, int>
     */
    public function findIdsByPartable(PartableTypeEnum $partableType, array $partableIds): Collection;
}
