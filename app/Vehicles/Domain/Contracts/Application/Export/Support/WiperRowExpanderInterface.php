<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Export\Support;

use Illuminate\Support\Collection;

interface WiperRowExpanderInterface
{
    public function expand(Collection $vehicles): Collection;
}

