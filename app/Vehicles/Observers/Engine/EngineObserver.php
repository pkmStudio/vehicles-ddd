<?php

declare(strict_types=1);

namespace App\Vehicles\Observers\Engine;

use App\Vehicles\Jobs\Engine\InvalidateMpCardsByEngineJob;
use App\Vehicles\Models\Engine;

class EngineObserver
{
    public function updated(Engine $engine): void
    {
        InvalidateMpCardsByEngineJob::dispatch((int) $engine->id);
    }
}
