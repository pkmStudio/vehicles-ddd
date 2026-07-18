<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовая Eloquent-модель копий Warehouse MoySklad с открытым mass-assignment.
 */
abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
