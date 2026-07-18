<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовая Eloquent-модель копий Warehouse Catalog с открытым mass-assignment.
 */
abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
