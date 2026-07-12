<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\Repositories;

use App\Warehouse\Export\Domain\ModelData\TypeData;
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
