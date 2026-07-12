<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Repositories;

use App\Warehouse\Import\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Порт чтения типов Warehouse-номенклатуры для резолва type_id при импорте.
 */
interface TypeRepositoryInterface
{
    /**
     * Возвращает все типы номенклатуры.
     *
     * @return Collection<int, TypeData>
     */
    public function all(): Collection;
}
