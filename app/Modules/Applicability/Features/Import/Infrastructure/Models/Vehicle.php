<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends AbstractModel
{
    public $timestamps = false;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
