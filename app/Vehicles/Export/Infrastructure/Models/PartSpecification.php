<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Без generic partable(): MorphTo — раз Vehicle/Engine дублируются по фичам, у "владельца"
 * нет единого класса, к которому можно было бы резолвиться (см. plan.md §11, п.9).
 */
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

    public function vehicle(): BelongsTo
    {
        return $this
            ->belongsTo(Vehicle::class, 'partable_id', 'id')
            ->where('part_specifications.partable_type', PartableTypeEnum::VEHICLE->value);
    }
}
