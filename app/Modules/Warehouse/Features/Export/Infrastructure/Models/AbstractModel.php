<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовая Eloquent-модель копий Warehouse Export с открытым mass-assignment для чтения.
 */
abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
