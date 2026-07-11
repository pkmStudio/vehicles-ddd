<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends BaseModel
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
