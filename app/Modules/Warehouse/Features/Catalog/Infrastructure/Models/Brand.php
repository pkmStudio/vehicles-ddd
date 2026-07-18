<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия бренда Warehouse для точечных Catalog-мутаций.
 */
class Brand extends AbstractModel
{
    /**
     * Возвращает номенклатуру бренда.
     */
    public function nomenclatures(): HasMany
    {
        return $this->hasMany(
            related: Nomenclature::class,
        );
    }
}
