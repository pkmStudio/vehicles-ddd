<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Models;

use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PartSpecification extends BaseModel
{
    protected $casts = [
        'template' => DetailTemplateEnum::class,
        'details' => 'array',
    ];

    // RELATIONS
    public function featureValue(): BelongsTo
    {
        return $this->belongsTo(FeatureValue::class, 'feature_value_id', 'id');
    }

    public function partable(): MorphTo
    {
        return $this->morphTo();
    }

    public function vehicle(): BelongsTo
    {
        return $this
            ->belongsTo(Vehicle::class, 'partable_id', 'id')
            ->where('part_specifications.partable_type', Vehicle::class);
    }
}
