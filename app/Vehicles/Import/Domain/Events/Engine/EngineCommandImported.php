<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Events\Engine;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class EngineCommandImported
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct() {}
}
