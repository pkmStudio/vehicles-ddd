<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eloquent-копия Warehouse-номенклатуры для чтения экспортируемых товаров.
 */
class Nomenclature extends AbstractModel
{
    protected $casts = [
        'details' => 'array',
        'material' => 'array',
        'vehicle_type' => 'array',
    ];

    /**
     * Возвращает тип Warehouse-номенклатуры.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(
            related: Type::class,
        );
    }

    /**
     * Возвращает бренд номенклатуры.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(
            related: Brand::class,
        );
    }

    /**
     * Возвращает наборы, в которые входит номенклатура.
     */
    public function kits(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                related: Kit::class,
                table: 'kit_nomenclature',
                foreignPivotKey: 'nomenclature_id',
                relatedPivotKey: 'kit_id',
            )
            ->withPivot('sort')
            ->orderBy('kit_nomenclature.sort');
    }
}
