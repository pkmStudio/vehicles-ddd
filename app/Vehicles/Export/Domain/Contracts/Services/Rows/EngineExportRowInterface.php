<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services\Rows;

use App\Vehicles\Export\Domain\ModelData\Engine\EngineData;

interface EngineExportRowInterface
{
    public function getBaseHeadings(): array;

    public function getBaseData(EngineData $engine): array;
}
