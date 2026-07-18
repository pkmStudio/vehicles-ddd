<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия упаковочного размера, нужна Warehouse Export для набора.
 */
class PackDimension extends AbstractModel
{
    protected $casts = [
        'generated' => 'boolean',
    ];

    /**
     * Возвращает тип номенклатуры, к которому относится упаковочный размер.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(
            related: Type::class,
        );
    }

    /**
     * Возвращает наборы с этим упаковочным размером.
     */
    public function kits(): HasMany
    {
        return $this->hasMany(
            related: Kit::class,
            foreignKey: 'pack_dimension_id',
        );
    }
}
