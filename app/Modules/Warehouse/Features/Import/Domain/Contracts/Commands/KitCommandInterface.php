<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;

/**
 * Порт записи Warehouse-набора (Kit) и его состава.
 */
interface KitCommandInterface
{
    /**
     * @param  array<int, int>  $nomenclatureIds  id номенклатур в порядке артикулов строки —
     *                                            порядок становится `sort` в pivot-таблице.
     */
    public function updateById(KitData $data, array $nomenclatureIds): KitData;

    /**
     * @param  array<int, int>  $nomenclatureIds  id номенклатур в порядке артикулов строки —
     *                                            порядок становится `sort` в pivot-таблице.
     */
    public function create(KitData $data, array $nomenclatureIds): KitData;
}
