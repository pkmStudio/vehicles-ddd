<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Models;

/**
 * Eloquent-копия упаковочного размера Warehouse для записи в Import-фиче.
 */
class PackDimension extends AbstractModel
{
    protected $casts = [
        'generated' => 'boolean',
    ];
}
