<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends BaseModel
{
    // RELATIONS
    public function values(): HasMany
    {
        return $this->hasMany(FeatureValue::class, 'feature_id', 'id');
    }
}
