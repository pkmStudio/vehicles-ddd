<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия Warehouse-типа. Catalog только читает типы, но не мутирует их.
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
