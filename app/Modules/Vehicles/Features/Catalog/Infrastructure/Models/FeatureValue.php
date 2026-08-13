<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Представляет Eloquent-модель значения особенности part specifications внутри фичи Catalog.
 */
class FeatureValue extends AbstractModel
{
    /**
     * Возвращает особенность, к которой относится значение.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Возвращает спецификации деталей с этим значением.
     */
    public function partSpecifications(): HasMany
    {
        return $this->hasMany(PartSpecification::class);
    }
}
