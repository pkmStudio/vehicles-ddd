<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\ProviderEnum;

class Manufacturer extends AbstractModel
{
    protected $casts = [
        'provider' => ProviderEnum::class,
    ];

    public $timestamps = false;
}
