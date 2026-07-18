<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия бренда для маппинга Warehouse-номенклатуры в МойСклад.
 */
class Brand extends AbstractModel
{
    /**
     * Возвращает номенклатуру бренда в границах MoySklad-фичи.
     */
    public function nomenclatures(): HasMany
    {
        return $this->hasMany(
            related: Nomenclature::class,
        );
    }
}
