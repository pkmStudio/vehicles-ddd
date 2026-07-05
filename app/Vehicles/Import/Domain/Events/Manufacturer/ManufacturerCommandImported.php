<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Events\Manufacturer;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class ManufacturerCommandImported
{
    use Dispatchable, SerializesModels;

    public function __construct() {}
}
