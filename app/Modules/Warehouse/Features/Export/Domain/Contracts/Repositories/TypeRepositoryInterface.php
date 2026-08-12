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
     * Шаги:
     * 1) Прочитать справочник типов номенклатуры.
     * 2) Упорядочить типы стабильно для Excel-листа.
     * 3) Вернуть коллекцию TypeData.
     *
     * @return Collection<int, TypeData>
     */
    public function all(): Collection;

    /**
     * Возвращает тип номенклатуры по id.
     *
     * Шаги:
     * 1) Принять id типа из export workflow.
     * 2) Найти тип в источнике Warehouse.
     * 3) Вернуть TypeData или null, если тип отсутствует.
     */
    public function findById(int $id): ?TypeData;
}
