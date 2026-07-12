<?php

declare(strict_types=1);

namespace App\Warehouse\WiperAdapterAudit\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent-копия Warehouse-номенклатуры для чтения details адаптеров.
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
}
