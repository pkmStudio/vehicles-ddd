<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

class Manufacturer extends AbstractModel
{
    protected $casts = [
        'provider' => ProviderEnum::class,
    ];

    public $timestamps = false;
}
