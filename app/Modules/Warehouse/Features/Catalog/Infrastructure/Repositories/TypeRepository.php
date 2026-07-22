<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\TypeData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Type;

/**
 * Читает Warehouse-типы для проверки внешних Catalog-мутаций.
 */
final readonly class TypeRepository implements TypeRepositoryInterface
{
    /**
     * Возвращает тип по id или null.
     */
    public function findById(int $id): ?TypeData
    {
        return TypeData::optional(Type::query()->find($id));
    }
}
