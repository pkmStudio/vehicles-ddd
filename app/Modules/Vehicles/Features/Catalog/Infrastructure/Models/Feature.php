<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Представляет Eloquent-модель особенности part specifications внутри фичи Catalog.
 */
class Feature extends AbstractModel
{
    /**
     * Возвращает значения особенности.
     */
    public function values(): HasMany
    {
        return $this->hasMany(FeatureValue::class);
    }
}
