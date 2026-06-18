<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    public $fillable = [
        'entity_type',
        'name',
    ];

    // RELATIONS
    public function values(): HasMany
    {
        return $this->hasMany(FeatureValue::class, 'feature_id', 'id');
    }
}
