<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия типа Warehouse-номенклатуры для экспорта и справочников.
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

    /**
     * Возвращает упаковочные размеры этого типа.
     */
    public function packDimensions(): HasMany
    {
        return $this->hasMany(
            related: PackDimension::class,
        );
    }

    /**
     * Возвращает наборы этого типа.
     */
    public function kits(): HasMany
    {
        return $this->hasMany(
            related: Kit::class,
        );
    }
}
