<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent-копия Warehouse-номенклатуры для Maintenance — только чтение состава набора при
 * пересчёте.
 */
class Nomenclature extends AbstractModel
{
    protected $casts = [
        'details' => 'array',
        'material' => 'array',
        'vehicle_type' => 'array',
    ];

    /**
     * Возвращает тип номенклатуры — нужен KitProperties для резолва стратегии состава.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(
            related: Type::class,
        );
    }
}
