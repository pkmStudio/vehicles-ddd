<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Models;

class Kit extends AbstractModel
{
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
