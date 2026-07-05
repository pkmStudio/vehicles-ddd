<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services\Expanders;

use Illuminate\Support\Collection;

interface WiperRowExpanderInterface
{
    public function expand(Collection $vehicles): Collection;
}
