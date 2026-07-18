<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Базовая Eloquent-модель копий Warehouse Import с открытым mass-assignment: запись идёт через
 * Command с фиксированным набором полей из Data, поэтому mass-assignment безопасен.
 */
abstract class AbstractModel extends Model
{
    protected $guarded = [];
}
