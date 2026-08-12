<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Порт чтения типов Warehouse-номенклатуры для резолва type_id при импорте.
 */
interface TypeRepositoryInterface
{
    /**
     * Возвращает все типы номенклатуры.
     *
     * Шаги:
     * 1) Прочитать типы Warehouse-номенклатуры из persistence.
     * 2) Собрать их в коллекцию для резолва строки импорта.
     * 3) Вернуть collection из TypeData без Eloquent-моделей.
     *
     * @return Collection<int, TypeData>
     */
    public function all(): Collection;
}
