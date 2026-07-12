<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Models;

/**
 * Eloquent-копия Warehouse-номенклатуры для записи в Import-фиче.
 */
class Nomenclature extends AbstractModel
{
    protected $casts = [
        'details' => 'array',
        'material' => 'array',
        'vehicle_type' => 'array',
    ];
}
