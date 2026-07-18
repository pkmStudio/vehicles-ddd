<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия типа Warehouse-номенклатуры для группировки товаров МойСклад.
 */
class Type extends AbstractModel
{
    /**
     * Возвращает номенклатуру этого типа.
     */
    public function nomenclatures(): HasMany
    {
        return $this->hasMany(
            related: Nomenclature::class,
        );
    }
}
