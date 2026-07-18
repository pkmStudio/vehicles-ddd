<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends AbstractModel
{
    // RELATIONS
    public function values(): HasMany
    {
        return $this->hasMany(FeatureValue::class, 'feature_id', 'id');
    }
}
