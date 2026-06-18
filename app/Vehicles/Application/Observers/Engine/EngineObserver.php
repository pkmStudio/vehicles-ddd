<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Observers\Engine;

use App\Vehicles\Application\Jobs\Engine\InvalidateMpCardsByEngineJob;
use App\Vehicles\Domain\Models\Engine;

class EngineObserver
{
    public function updated(Engine $engine): void
    {
        InvalidateMpCardsByEngineJob::dispatch((int) $engine->id);
    }
}
