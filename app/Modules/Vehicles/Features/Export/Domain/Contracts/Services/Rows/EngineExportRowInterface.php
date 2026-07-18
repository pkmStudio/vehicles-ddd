<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Rows;

use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;

interface EngineExportRowInterface
{
    public function getBaseHeadings(): array;

    public function getBaseData(EngineData $engine): array;
}
