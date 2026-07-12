<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Domain\Contracts\Repositories;

use App\Warehouse\Export\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-номенклатуры для Export-фичи.
 */
interface NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру выбранного типа с нужными связями.
     *
     * @return Collection<int, NomenclatureData>
     */
    public function forType(int $typeId): Collection;
}
