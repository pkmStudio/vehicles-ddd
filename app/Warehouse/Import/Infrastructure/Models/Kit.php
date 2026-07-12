<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eloquent-копия Warehouse-набора для записи в Import-фиче.
 */
class Kit extends AbstractModel
{
    protected $casts = [
        'complement' => 'boolean',
        'is_sale_separately' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Возвращает номенклатуры набора в порядке pivot-поля sort.
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
