<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Enums\EngineExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

/**
 * Read port данных двигателей для Excel export.
 */
interface EngineRepositoryInterface
{
    /**
     * Для листа экспорта двигателей.
     *
     * Шаги:
     * 1) Выбрать read projection по enum листа.
     * 2) Вернуть typed engine snapshots без Eloquent наружу.
     *
     * @return Collection<int, EngineData>
     */
    public function forSheet(EngineExportSheetEnum $sheet): Collection;
}
