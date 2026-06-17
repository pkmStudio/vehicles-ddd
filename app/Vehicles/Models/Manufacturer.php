<?php

declare(strict_types=1);

namespace App\Vehicles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    protected $fillable = [
        'mfa_id',
        'name',
        'provider',
    ];

    public $timestamps = false;

    // RELATIONS
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
