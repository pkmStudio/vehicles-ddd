<?php

declare(strict_types=1);

namespace App\Warehouse\WiperAdapterAudit\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовая Eloquent-модель WiperAdapterAudit-фичи с общей mass-assignment политикой.
 */
abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
