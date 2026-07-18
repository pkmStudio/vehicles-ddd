<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Infrastructure\Models;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends AbstractModel
{
    protected $casts = [
        'provider' => ProviderEnum::class,
    ];

    public $timestamps = false;

    // RELATIONS
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
