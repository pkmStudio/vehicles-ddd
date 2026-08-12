<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Models;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Без generic partable(): MorphTo — раз Vehicle/Engine дублируются по фичам, у "владельца"
 * нет единого класса, к которому можно было бы резолвиться (см. plan.md §11, п.9).
 */
class PartSpecification extends AbstractModel
{
    protected $casts = [
        'template' => DetailTemplateEnum::class,
        'details' => 'array',
    ];

    // RELATIONS
    /**
     * Связь specification со значением справочника фичи.
     *
     * Шаги:
     * - Описать belongsTo связь через feature_value_id.
     * - Вернуть query relation на export-копию FeatureValue.
     */
    public function featureValue(): BelongsTo
    {
        return $this->belongsTo(FeatureValue::class, 'feature_value_id', 'id');
    }
}
