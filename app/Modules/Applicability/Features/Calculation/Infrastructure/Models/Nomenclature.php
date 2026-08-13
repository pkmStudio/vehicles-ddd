<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nomenclature extends AbstractModel
{
    protected $casts = [
        'details' => 'array',
        'material' => 'array',
        'vehicle_type' => 'array',
    ];

    /**
     * Возвращает тип складской номенклатуры.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }
}
