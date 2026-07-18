<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия бренда, нужна Warehouse Export для связи номенклатуры.
 */
class Brand extends AbstractModel
{
    /**
     * Возвращает номенклатуру бренда в границах Export-фичи.
     */
    public function nomenclatures(): HasMany
    {
        return $this->hasMany(
            related: Nomenclature::class,
        );
    }
}
