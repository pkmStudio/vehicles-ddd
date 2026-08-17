<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\ManufacturerData;
use Illuminate\Support\Collection;

/**
 * Read port данных производителей для Excel export.
 */
interface ManufacturerRepositoryInterface
{
    /**
     * Возвращает всех производителей для внешнего файла импорта/экспорта.
     *
     * @return Collection<int, ManufacturerData>
     */
    public function all(): Collection;
}
