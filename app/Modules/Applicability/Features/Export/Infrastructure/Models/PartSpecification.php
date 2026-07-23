<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartSpecification extends AbstractModel
{
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'partable_id');
    }
}
