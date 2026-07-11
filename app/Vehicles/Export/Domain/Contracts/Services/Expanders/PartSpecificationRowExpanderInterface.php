<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services\Expanders;

use App\Vehicles\Export\Domain\DTOs\PartSpecificationExportRowDTO;
use App\Vehicles\Export\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

interface PartSpecificationRowExpanderInterface
{
    /**
     * @param  Collection<int, EngineData>  $entities
     * @return Collection<int, PartSpecificationExportRowDTO>
     */
    public function expand(Collection $entities): Collection;
}
