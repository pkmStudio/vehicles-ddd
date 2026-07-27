<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\Export\Domain\ModelData\PackDimensionData;
use Illuminate\Support\Collection;

/**
 * Порт чтения упаковочных размеров Warehouse для Export-фичи.
 */
interface PackDimensionRepositoryInterface
{
    /**
     * Возвращает все упаковочные размеры для Excel-выгрузки.
     *
     * @return Collection<int, PackDimensionData>
     */
    public function all(): Collection;
}
