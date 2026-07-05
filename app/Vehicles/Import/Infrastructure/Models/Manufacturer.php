<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends BaseModel
{
    public $timestamps = false;

    // RELATIONS
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
