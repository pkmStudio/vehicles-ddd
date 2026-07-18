<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Export\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Порт чтения типов Warehouse-номенклатуры для Export-фичи.
 */
interface TypeRepositoryInterface
{
    /**
     * Возвращает все типы номенклатуры для справочного листа.
     *
     * @return Collection<int, TypeData>
     */
    public function all(): Collection;

    /**
     * Возвращает тип номенклатуры по id.
     */
    public function find(int $id): ?TypeData;
}
