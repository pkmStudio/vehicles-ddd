<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent-копия упаковочного размера Warehouse для точечных Catalog-мутаций.
 */
class PackDimension extends AbstractModel
{
    protected $casts = [
        'generated' => 'boolean',
    ];

    /**
     * Возвращает тип номенклатуры упаковочного размера.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(
            related: Type::class,
        );
    }

    /**
     * Возвращает наборы, использующие эту упаковку.
     */
    public function kits(): HasMany
    {
        return $this->hasMany(
            related: Kit::class,
            foreignKey: 'pack_dimension_id',
        );
    }
}
