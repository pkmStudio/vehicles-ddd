<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent-копия Warehouse-номенклатуры для записи в Import-фиче.
 */
class Nomenclature extends AbstractModel
{
    protected $casts = [
        'details' => 'array',
        'material' => 'array',
        'vehicle_type' => 'array',
    ];

    /**
     * Возвращает тип номенклатуры — нужен только сборке Kit (резолв стратегии состава).
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(
            related: Type::class,
        );
    }
}
