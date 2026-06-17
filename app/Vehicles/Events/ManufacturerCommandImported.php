<?php

declare(strict_types=1);

namespace App\Vehicles\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManufacturerCommandImported
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct() {}
}
