<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Infrastructure\Models;

/**
 * Eloquent-копия упаковочного размера Warehouse для чтения/записи в Packaging-фиче.
 */
class PackDimension extends AbstractModel
{
    protected $casts = [
        'generated' => 'boolean',
    ];
}
