<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Enums\EngineExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

interface EngineRepositoryInterface
{
    /**
     * Для листа экспорта двигателей.
     *
     * @return Collection<int, EngineData>
     */
    public function forSheet(EngineExportSheetEnum $sheet): Collection;
}
