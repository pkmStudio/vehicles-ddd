<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureValue extends Model
{
    public $fillable = [
        'feature_id',
        'name',
        'short_code',
    ];

    // RELATIONS
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }
}
