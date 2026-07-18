<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Export\Domain\ModelData\NomenclatureData;
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
