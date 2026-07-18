<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent-копия связки Warehouse-номенклатуры с внешней системой МойСклад.
 */
class NomenclatureIntegration extends AbstractModel
{
    public const string PROVIDER = 'moysklad';

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_SYNCED = 'synced';

    public const string STATUS_FAILED = 'failed';

    public const string STATUS_DELETED = 'deleted';

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    /**
     * Возвращает локальную номенклатуру, если она ещё существует.
     */
    public function nomenclature(): BelongsTo
    {
        return $this->belongsTo(
            related: Nomenclature::class,
        );
    }
}
