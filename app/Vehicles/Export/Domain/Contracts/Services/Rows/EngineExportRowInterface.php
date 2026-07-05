<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services\Rows;

use App\Vehicles\Domain\Models\Engine;

interface EngineExportRowInterface
{
    public function getBaseHeadings(): array;

    public function getBaseData(Engine $engine): array;
}
