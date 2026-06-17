<?php

declare(strict_types=1);

namespace App\Vehicles\Observers;

use App\Vehicles\Jobs\InvalidateMpCardsByEngineJob;
use App\Vehicles\Models\Engine;

class EngineObserver
{
    public function updated(Engine $engine): void
    {
        InvalidateMpCardsByEngineJob::dispatch((int) $engine->id);
    }
}
