<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Eloquent-копия Warehouse-номенклатуры для синхронизации с МойСклад.
 */
class Nomenclature extends AbstractModel
{
    protected $casts = [
        'details' => 'array',
        'material' => 'array',
        'vehicle_type' => 'array',
    ];

    /**
     * Возвращает тип номенклатуры.
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
     * Возвращает integration-запись МойСклад для этой номенклатуры.
     */
    public function moySkladIntegration(): HasOne
    {
        return $this
            ->hasOne(
                related: NomenclatureIntegration::class,
            )
            ->where('provider', NomenclatureIntegration::PROVIDER);
    }
}
