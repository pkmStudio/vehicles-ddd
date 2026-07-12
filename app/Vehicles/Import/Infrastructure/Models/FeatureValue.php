<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureValue extends AbstractModel
{
    // RELATIONS
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }
}
