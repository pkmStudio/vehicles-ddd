<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\TypeData;

/**
 * Порт чтения Warehouse-типов. Типы в Catalog пока нельзя мутировать.
 */
interface TypeRepositoryInterface
{
    /**
     * Возвращает тип по id или null.
     */
    public function find(int $id): ?TypeData;
}
