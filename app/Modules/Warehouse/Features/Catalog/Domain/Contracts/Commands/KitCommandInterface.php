<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;

/**
 * Порт записи Warehouse-наборов и их состава.
 */
interface KitCommandInterface
{
    /**
     * Создаёт набор и полностью записывает его состав.
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    public function create(KitData $data, array $nomenclatureIds): KitData;

    /**
     * Обновляет набор и полностью переписывает его состав.
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    public function update(KitData $data, array $nomenclatureIds): KitData;

    /**
     * Удаляет набор и его pivot-состав вручную.
     */
    public function deleteById(int $id): void;
}
