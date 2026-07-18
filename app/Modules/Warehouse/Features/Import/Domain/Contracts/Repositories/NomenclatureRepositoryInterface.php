<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

/**
 * Порт чтения уже сохранённой Warehouse-номенклатуры — нужен только сборке Kit (резолв состава
 * набора по артикулам). Сам построчный импорт номенклатуры этот порт не использует — он только
 * пишет (см. `Contracts\Commands\NomenclatureCommandInterface`).
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает найденные номенклатуры, индексированные по part_number. Отсутствующие в БД
     * артикулы просто не попадут в результат — вызывающий сам решает, как на это реагировать.
     *
     * @param  array<int, string>  $partNumbers
     * @return Collection<string, NomenclatureData>
     */
    public function findByPartNumbers(array $partNumbers): Collection;
}
