<?php

declare(strict_types=1);

namespace App\Vehicles\Maintenance\Infrastructure\Models;

use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Копия для Maintenance — без relation на FeatureValue: FeatureValue-модель здесь не
 * дублируется (Maintenance читает только колонку feature_value_id, не саму связь).
 * Без generic partable(): MorphTo — та же причина, что у Import/Export (см. plan.md §11, п.9);
 * у Maintenance к тому же и Engine не дублируется, так что резолвить всё равно нечем.
 */
class PartSpecification extends AbstractModel
{
    protected $casts = [
        'template' => DetailTemplateEnum::class,
        'details' => 'array',
    ];

    // RELATIONS
    public function vehicle(): BelongsTo
    {
        return $this
            ->belongsTo(Vehicle::class, 'partable_id', 'id')
            ->where('part_specifications.partable_type', PartableTypeEnum::VEHICLE->value);
    }
}
