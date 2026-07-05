<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services\Expanders;

use Illuminate\Support\Collection;

interface PartSpecificationRowExpanderInterface
{
    public function expand(Collection $entities): Collection;
}
