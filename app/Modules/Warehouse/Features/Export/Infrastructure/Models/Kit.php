<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eloquent-копия Warehouse-набора для чтения состава в Export-фиче.
 */
class Kit extends AbstractModel
{
    protected $casts = [
        'complement' => 'boolean',
        'is_sale_separately' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Возвращает упаковочный размер набора.
     */
    public function packDimension(): BelongsTo
    {
        return $this->belongsTo(
            related: PackDimension::class,
            foreignKey: 'pack_dimension_id',
        );
    }

    /**
     * Возвращает тип Warehouse-номенклатуры, к которому относится набор.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(
            related: Type::class,
        );
    }

    /**
     * Возвращает номенклатуру набора в порядке pivot-поля sort.
     */
    public function nomenclatures(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                related: Nomenclature::class,
                table: 'kit_nomenclature',
                foreignPivotKey: 'kit_id',
                relatedPivotKey: 'nomenclature_id',
            )
            ->withPivot('sort')
            ->orderBy('kit_nomenclature.sort');
    }
}
