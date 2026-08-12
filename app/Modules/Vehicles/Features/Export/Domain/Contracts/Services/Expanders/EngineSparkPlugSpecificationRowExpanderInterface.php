<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

interface EngineSparkPlugSpecificationRowExpanderInterface
{
    /**
     * @param  Collection<int, EngineData>  $entities
     * @return Collection<int, PartSpecificationExportRowDTO>
     */
    public function expand(Collection $entities): Collection;
}
