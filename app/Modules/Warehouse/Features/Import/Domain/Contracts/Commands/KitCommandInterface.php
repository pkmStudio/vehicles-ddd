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
     * Находит набор по $kitId (если указан) либо по `$data->importHash`, обновляет либо создаёт,
     * затем полностью переattach'ивает состав (не diff — как в исходном сервисе dan-center).
     *
     * @param  array<int, int>  $nomenclatureIds  id номенклатур в порядке артикулов строки —
     *                                            порядок становится `sort` в pivot-таблице.
     */
    public function upsert(KitData $data, ?int $kitId, array $nomenclatureIds): KitData;
}
