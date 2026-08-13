<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartSpecification extends AbstractModel
{
    /**
     * Возвращает автомобиль, которому принадлежит экспортируемая спецификация.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'partable_id');
    }
}
